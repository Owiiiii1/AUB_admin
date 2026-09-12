<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecureFile extends Model
{
    use SoftDeletes;

    public const VARIANT_ORIGINAL = 'original';

    public const VARIANT_THUMBNAIL = 'thumbnail';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'attachable_type',
        'attachable_id',
        'category',
        'variant',
        'parent_id',
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'size_bytes',
        'sha256',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function thumbnail(): ?self
    {
        if ($this->variant === self::VARIANT_THUMBNAIL) {
            return $this;
        }

        if ($this->relationLoaded('variants')) {
            return $this->variants->firstWhere('variant', self::VARIANT_THUMBNAIL);
        }

        return $this->variants()->where('variant', self::VARIANT_THUMBNAIL)->first();
    }

    public function isSensitive(): bool
    {
        $category = config('aub-files.categories.'.$this->category, []);

        return ($category['sensitivity'] ?? 'sensitive') === 'sensitive';
    }

    public function auditView(): bool
    {
        return (bool) (config('aub-files.categories.'.$this->category.'.audit_view') ?? true);
    }

    public function inlineAllowed(): bool
    {
        return (bool) (config('aub-files.categories.'.$this->category.'.inline_allowed') ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function publicMetadata(): array
    {
        return [
            'file_uuid' => $this->uuid,
            'category' => $this->category,
            'mime_type' => $this->mime_type,
            'size' => $this->size_bytes,
            'url' => url('/api/v1/files/'.$this->uuid),
        ];
    }
}
