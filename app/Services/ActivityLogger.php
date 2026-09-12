<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * @var list<string>
     */
    private const REDACT_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'api_key',
        'remember_token',
        'token',
        'authorization',
    ];

    /**
     * @param  array<string, mixed>|null  $properties
     */
    public function log(
        Request $request,
        string $action,
        string $subjectType,
        ?int $subjectId = null,
        ?string $subjectLabel = null,
        ?int $customerId = null,
        ?array $properties = null,
        ?int $studentId = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'customer_id' => $customerId,
            'student_id' => $studentId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'properties' => $properties,
            'route_name' => $request->route()?->getName(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function logModelChange(
        Request $request,
        string $action,
        string $subjectType,
        int $subjectId,
        ?string $subjectLabel,
        ?int $customerId = null,
        ?array $before = null,
        ?array $after = null,
        ?int $studentId = null,
    ): ActivityLog {
        $properties = [];

        if ($before !== null && $after !== null) {
            $properties['changes'] = $this->diffChanges($before, $after);
        } elseif ($after !== null) {
            $properties['attributes'] = $this->redact($after);
        } elseif ($before !== null) {
            $properties['attributes'] = $this->redact($before);
        }

        return $this->log(
            $request,
            $action,
            $subjectType,
            $subjectId,
            $subjectLabel,
            $customerId,
            $properties === [] ? null : $properties,
            $studentId,
        );
    }

    public function logForModel(
        Request $request,
        string $action,
        Model $model,
        string $subjectType,
        ?int $customerId = null,
        ?array $before = null,
        ?array $after = null,
    ): ActivityLog {
        $studentId = $model instanceof \App\Models\Student ? (int) $model->getKey() : null;

        return $this->logModelChange(
            $request,
            $action,
            $subjectType,
            (int) $model->getKey(),
            $this->resolveLabel($model, $subjectType),
            $studentId !== null ? null : $customerId,
            $before,
            $after,
            $studentId,
        );
    }

    public function logSecureFileEvent(
        string $action,
        \App\Models\SecureFile $file,
        ?\App\Models\User $actor = null,
        ?Request $request = null,
    ): void {
        if ($action === 'secure_file.viewed'
            && (! $file->auditView() || $file->variant === \App\Models\SecureFile::VARIANT_THUMBNAIL)) {
            return;
        }

        $request ??= request();
        if (! $request instanceof Request) {
            $request = Request::create('/artisan/secure-files', 'GET');
        }

        $this->log(
            $request,
            $action,
            'secure_file',
            $file->id,
            $file->uuid,
            null,
            [
                'file_uuid' => $file->uuid,
                'attachable_type' => $file->attachable_type,
                'attachable_id' => $file->attachable_id,
                'category' => $file->category,
                'actor_user_id' => $actor?->id ?? $request->user()?->id,
                'variant' => $file->variant,
            ],
            $file->attachable_type === 'student' ? (int) $file->attachable_id : null,
        );
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function diffChanges(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $key => $value) {
            if (! array_key_exists($key, $before)) {
                continue;
            }

            if ($this->shouldRedact((string) $key)) {
                if ($before[$key] !== $value) {
                    $changes[$key] = ['***', '***'];
                }

                continue;
            }

            if ($before[$key] != $value) {
                $changes[$key] = [$before[$key], $value];
            }
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function redact(array $data): array
    {
        $redacted = [];

        foreach ($data as $key => $value) {
            $redacted[$key] = $this->shouldRedact((string) $key) ? '***' : $value;
        }

        return $redacted;
    }

    private function shouldRedact(string $key): bool
    {
        return in_array($key, self::REDACT_KEYS, true);
    }

    private function resolveLabel(Model $model, string $subjectType): ?string
    {
        return match ($subjectType) {
            'customer' => (string) ($model->getAttribute('name') ?? $model->getAttribute('email') ?? ''),
            'student' => (string) ($model->getAttribute('name') ?? $model->getAttribute('email') ?? ''),
            'teacher' => (string) ($model->getAttribute('name') ?? $model->getAttribute('email') ?? ''),
            'lesson' => (string) ($model->getAttribute('name') ?? ''),
            'user', 'profile' => (string) ($model->getAttribute('name') ?? $model->getAttribute('email') ?? ''),
            'role' => (string) ($model->getAttribute('name') ?? $model->getAttribute('slug') ?? ''),
            'ai_provider' => (string) ($model->getAttribute('label') ?? $model->getAttribute('provider') ?? ''),
            default => (string) ($model->getAttribute('name') ?? $model->getKey()),
        } ?: null;
    }
}
