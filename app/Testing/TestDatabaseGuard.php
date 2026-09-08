<?php

namespace App\Testing;

use RuntimeException;

final class TestDatabaseGuard
{
    public const EXPECTED_CONNECTION = 'mysql';

    public const EXPECTED_DATABASE = 'aub_test';

    public const PRODUCTION_DATABASE = 'aub';

    public static function assertSafe(?string $connection = null, ?string $database = null): void
    {
        $connection ??= (string) config('database.default');
        $database ??= (string) config('database.connections.'.$connection.'.database');

        if ($connection !== self::EXPECTED_CONNECTION || $database !== self::EXPECTED_DATABASE) {
            throw new RuntimeException('Refusing to run tests against non-test database.');
        }
    }
}
