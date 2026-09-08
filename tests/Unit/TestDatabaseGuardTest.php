<?php

namespace Tests\Unit;

use App\Testing\TestDatabaseGuard;
use RuntimeException;
use Tests\TestCase;

class TestDatabaseGuardTest extends TestCase
{
    public function test_allows_mysql_aub_test(): void
    {
        TestDatabaseGuard::assertSafe('mysql', 'aub_test');

        $this->assertTrue(true);
    }

    public function test_refuses_production_database_name(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to run tests against non-test database.');

        TestDatabaseGuard::assertSafe('mysql', 'aub');
    }

    public function test_refuses_sqlite_memory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to run tests against non-test database.');

        TestDatabaseGuard::assertSafe('sqlite', ':memory:');
    }
}
