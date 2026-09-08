<?php

namespace Tests;

use App\Testing\TestDatabaseGuard;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        if ($this->app->environment('testing')) {
            TestDatabaseGuard::assertSafe();
        }
    }
}
