<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ShowScheduleRequest;
use App\Http\Resources\Api\ScheduleResource;
use App\Http\Resources\Api\TeacherScheduleResource;
use App\Http\Responses\ApiResponse;
use App\Models\Student;
use App\Models\User;
use App\Services\WeeklySchedule\MobileScheduleService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly MobileScheduleService $schedule,
    ) {}

    public function student(ShowScheduleRequest $request): JsonResponse
    {
        $this->assertAccountType($request->user(), User::TYPE_STUDENT);

        $student = $request->user()?->studentProfile;
        if ($student === null) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        $payload = $this->schedule->forStudent($student, $request->validated('week'));

        return ApiResponse::success((new ScheduleResource($payload))->resolve());
    }

    public function child(ShowScheduleRequest $request, Student $student): JsonResponse
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

        $payload = $this->schedule->forStudent($student, $request->validated('week'));

        return ApiResponse::success((new ScheduleResource($payload))->resolve());
    }

    public function teacher(ShowScheduleRequest $request): JsonResponse
    {
        $this->assertAccountType($request->user(), User::TYPE_TEACHER);

        $teacher = $request->user()?->teacherProfile;
        if ($teacher === null) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        $payload = $this->schedule->forTeacher($teacher, $request->validated('week'));

        return ApiResponse::success((new TeacherScheduleResource($payload))->resolve());
    }

    private function assertAccountType(?User $user, string $type): void
    {
        if ($user === null || $user->account_type !== $type) {
            throw new AccessDeniedHttpException('Forbidden.');
        }
    }
}
