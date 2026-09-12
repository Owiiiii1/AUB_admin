<?php

namespace Tests\Feature;

use App\Support\PublicStorageArchitectureGuard;
use Tests\TestCase;

class SecureFileArchitectureGuardTest extends TestCase
{
    public function test_application_source_does_not_use_public_disk_for_files(): void
    {
        $hits = (new PublicStorageArchitectureGuard)->scan(base_path());

        $this->assertSame([], $hits, json_encode($hits, JSON_PRETTY_PRINT));
    }

    public function test_forbidden_fixture_is_detected(): void
    {
        $hits = (new PublicStorageArchitectureGuard)->scan(
            base_path(),
            ['tests/Fixtures/Architecture'],
            [],
        );

        $this->assertNotEmpty($hits);
        $this->assertTrue(
            collect($hits)->contains(fn (array $hit): bool => str_contains($hit['snippet'], '/storage/')),
        );
    }
}
