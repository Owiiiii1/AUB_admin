<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveTeacherAttendanceRequest;
use App\Http\Requests\Api\ShowAttendanceHistoryRequest;
use App\Http\Resources\Api\AttendanceHistoryResource;
use App\Http\Resources\Api\TeacherAttendanceResource;
use App\Http\Responses\ApiResponse;
use App\Models\ScheduledLesson;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AttendanceHistoryService;
use App\Services\TeacherAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly TeacherAttendanceService $attendance,
        private readonly AttendanceHistoryService $history,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function student(ShowAttendanceHistoryRequest $request): JsonResponse
    {
        $this->assertAccountType($request->user(), User::TYPE_STUDENT);

        $student = $request->user()?->studentProfile;
        if ($student === null) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        $payload = $this->history->forStudent($student, $request->validated('month'));

        return ApiResponse::success((new AttendanceHistoryResource($payload))->resolve());
    }

    public function child(ShowAttendanceHistoryRequest $request, Student $student): JsonResponse
    {
        $this->assertAccountType($request->user(), User::TYPE_PARENT);

        $parent = $request->user()?->parentProfile;
        if ($parent === null) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        $ownsChild = $parent->students()->where('students.id', $student->id)->exists();
        if (! $ownsChild) {
            throw new NotFoundHttpException('Not found.');
        }

        $payload = $this->history->forStudent($student, $request->validated('month'));

        return ApiResponse::success((new AttendanceHistoryResource($payload))->resolve());
    }

    public function show(Request $request, ScheduledLesson $scheduledLesson): JsonResponse
    {
        $teacher = $this->teacher($request);
        $payload = $this->attendance->show($teacher, $scheduledLesson);

        return ApiResponse::success((new TeacherAttendanceResource($payload))->resolve());
    }

    public function update(SaveTeacherAttendanceRequest $request, ScheduledLesson $scheduledLesson): JsonResponse
    {
        $teacher = $this->teacher($request);
        $user = $request->user();
        if ($user === null) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        $result = $this->attendance->save($teacher, $user, $scheduledLesson, $request->rows());

        $this->activityLogger->log(
            $request,
            'attendance.updated',
            'scheduled_lesson',
            $scheduledLesson->id,
            null,
            null,
            [
                'scheduled_lesson_id' => $scheduledLesson->id,
                'changed_count' => $result['changed_count'],
            ],
        );

        return ApiResponse::success((new TeacherAttendanceResource($result['payload']))->resolve());
    }

    private function teacher(Request $request): \App\Models\Teacher
    {
        $user = $request->user();
        if ($user === null || $user->account_type !== User::TYPE_TEACHER) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        $teacher = $user->teacherProfile;
        if ($teacher === null) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        return $teacher;
    }

    private function assertAccountType(?User $user, string $type): void
    {
        if ($user === null || $user->account_type !== $type) {
            throw new AccessDeniedHttpException('Forbidden.');
        }
    }
}
