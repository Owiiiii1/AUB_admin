<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->integer('user_id') ?: null;
        $customerId = $request->integer('customer_id') ?: $request->integer('student_id') ?: null;

        $logs = ActivityLog::query()
            ->with([
                'user:id,name,email',
                'student:id,name,email',
            ])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($customerId, fn ($query) => $query->where('student_id', $customerId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(static fn (ActivityLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'subject_label' => $log->subject_label,
                'properties' => $log->properties,
                'route_name' => $log->route_name,
                'created_at' => optional($log->created_at)->toIso8601String(),
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
                'customer' => $log->student ? [
                    'id' => $log->student->id,
                    'name' => $log->student->name,
                    'email' => $log->student->email,
                ] : null,
            ]);

        $staffOptions = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->all();

        $customerOptions = Student::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(static fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ])
            ->all();

        return Inertia::render('Statistics/Logs', [
            'logs' => $logs,
            'filters' => [
                'user_id' => $userId,
                'customer_id' => $customerId,
            ],
            'staffOptions' => $staffOptions,
            'customerOptions' => $customerOptions,
        ]);
    }
}
