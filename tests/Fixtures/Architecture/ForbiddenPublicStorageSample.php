<?php

namespace Tests\Fixtures\Architecture;

/**
 * Deliberate forbidden sample for PublicStorageArchitectureGuard tests.
 * Not part of application source.
 */
class ForbiddenPublicStorageSample
{
    public function bad(): string
    {
        return '/storage/students/1/photo.jpg';
    }
}
