<?php

namespace Tests\Unit;

use App\Services\WeeklySchedule\MobileScheduleService;
use Carbon\Carbon;
use Tests\TestCase;

class MobileScheduleWeekBoundsTest extends TestCase
{
    private MobileScheduleService $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schedule = $this->app->make(MobileScheduleService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_application_timezone_is_europe_rome(): void
    {
        $this->assertSame('Europe/Rome', (string) config('app.timezone'));
    }

    public function test_sunday_late_evening_utc_is_already_monday_week_in_rome(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-13 22:30:00', 'UTC'));

        [$startsOn, $endsOn] = $this->schedule->resolveWeekBounds(null);

        $this->assertSame('2026-09-14', $startsOn->toDateString());
        $this->assertSame('2026-09-20', $endsOn->toDateString());
        $this->assertSame('Europe/Rome', $startsOn->timezoneName);
    }

    public function test_monday_early_morning_rome_starts_new_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 00:15:00', 'Europe/Rome'));

        [$startsOn, $endsOn] = $this->schedule->resolveWeekBounds(null);

        $this->assertSame('2026-09-14', $startsOn->toDateString());
        $this->assertSame('2026-09-20', $endsOn->toDateString());
    }

    public function test_dst_spring_forward_does_not_break_week_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-29 03:30:00', 'Europe/Rome'));

        [$sundayWeekStart] = $this->schedule->resolveWeekBounds(null);
        $this->assertSame('2026-03-23', $sundayWeekStart->toDateString());

        Carbon::setTestNow(Carbon::parse('2026-03-30 00:15:00', 'Europe/Rome'));

        [$mondayWeekStart, $mondayWeekEnd] = $this->schedule->resolveWeekBounds(null);
        $this->assertSame('2026-03-30', $mondayWeekStart->toDateString());
        $this->assertSame('2026-04-05', $mondayWeekEnd->toDateString());
    }
}
