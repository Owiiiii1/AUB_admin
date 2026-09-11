<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\ScheduledLesson;
use App\Models\ScheduleWeek;
use App\Models\Student;
use App\Models\User;
use App\Services\MobileDemoDataService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MobileDemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-11 21:41:00', 'Europe/Rome'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_command_runs_without_force_in_testing(): void
    {
        $this->assertSame(0, Artisan::call('aub:fill-mobile-demo'));
        $this->assertGreaterThan(0, ScheduledLesson::query()->where('notes', MobileDemoDataService::NOTES_MARKER)->count());
    }

    public function test_fills_current_weeks_lessons_and_attendance(): void
    {
        Artisan::call('aub:fill-mobile-demo', ['--force' => true]);

        $this->assertSame(3, ScheduleWeek::query()->where('status', ScheduleWeek::STATUS_PUBLISHED)->count());
        $this->assertGreaterThan(0, ScheduledLesson::query()->where('notes', MobileDemoDataService::NOTES_MARKER)->count());
        $this->assertGreaterThan(0, AttendanceRecord::query()->count());

        $studentUser = User::query()->where('email', MobileDemoDataService::STUDENT_EMAIL)->firstOrFail();
        $teacherUser = User::query()->where('email', MobileDemoDataService::TEACHER_EMAIL)->firstOrFail();
        $parentUser = User::query()->where('email', MobileDemoDataService::PARENT_EMAIL)->firstOrFail();
        $student = Student::query()->where('user_id', $studentUser->id)->firstOrFail();

        $studentJson = $this->withToken($this->loginToken($studentUser))
            ->getJson('/api/v1/schedule')
            ->assertOk()
            ->assertJsonPath('data.week.published', true)
            ->json('data');

        $this->assertNull($studentJson['empty_reason']);
        $friday = $studentJson['days'][4]['lessons'];
        $this->assertNotEmpty($friday);
        $this->assertContains('22:00', array_column($friday, 'starts_at'));
        $this->assertContains('cancelled', array_column($studentJson['days'][2]['lessons'], 'status'));
        $this->assertContains('moved', array_column($studentJson['days'][3]['lessons'], 'status'));

        $this->assertSame(User::TYPE_TEACHER, $teacherUser->fresh()->account_type);
        $this->assertNotNull($teacherUser->fresh()->teacherProfile);

        $history = $this->withToken($this->loginToken($studentUser))
            ->getJson('/api/v1/attendance?month=2026-09')
            ->assertOk()
            ->json('data');

        $this->assertGreaterThan(0, $history['summary']['marked']);
        $this->assertGreaterThan(0, $history['summary']['present']);
        $this->assertGreaterThan(0, $history['summary']['absent']);
        $this->assertGreaterThan(0, $history['summary']['excused']);

        $this->forgetAuthGuards();
        $teacherJson = $this->withToken($this->loginToken($teacherUser))
            ->getJson('/api/v1/teacher/schedule')
            ->assertOk()
            ->assertJsonPath('data.week.published', true)
            ->json('data');
        $this->assertNull($teacherJson['empty_reason']);
        $this->assertNotEmpty($teacherJson['days'][4]['lessons']);

        $this->forgetAuthGuards();
        $this->withToken($this->loginToken($parentUser))
            ->getJson('/api/v1/children/'.$student->id.'/schedule')
            ->assertOk()
            ->assertJsonPath('data.week.published', true);

        $this->forgetAuthGuards();
        $this->withToken($this->loginToken($parentUser))
            ->getJson('/api/v1/children/'.$student->id.'/attendance?month=2026-09')
            ->assertOk()
            ->assertJsonPath('data.summary.marked', $history['summary']['marked']);
    }

    public function test_rerun_is_idempotent_and_leaves_foreign_weeks_alone(): void
    {
        $foreign = ScheduleWeek::query()->create([
            'week_start_date' => '2026-12-28',
            'week_end_date' => '2027-01-01',
            'title' => 'Mobile API test week',
            'status' => ScheduleWeek::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        Artisan::call('aub:fill-mobile-demo', ['--force' => true]);
        $firstCount = ScheduledLesson::query()->where('notes', MobileDemoDataService::NOTES_MARKER)->count();
        $this->assertGreaterThan(0, $firstCount);

        Artisan::call('aub:fill-mobile-demo', ['--force' => true]);
        $this->assertSame(
            $firstCount,
            ScheduledLesson::query()->where('notes', MobileDemoDataService::NOTES_MARKER)->count(),
        );

        $this->assertTrue($foreign->refresh()->exists());
        $this->assertSame(0, $foreign->scheduledLessons()->count());
    }

    private function loginToken(User $user, bool $forget = false): string
    {
        $this->flushHeaders();
        $this->forgetAuthGuards();

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Owl iPhone',
        ])->assertOk()->json('data.token');

        $this->assertIsString($token);

        return $token;
    }

    private function forgetAuthGuards(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
