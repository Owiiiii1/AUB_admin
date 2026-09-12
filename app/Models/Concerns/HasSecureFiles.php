<?php

namespace App\Models\Concerns;

use App\Models\SecureFile;
use App\Support\SecureFileUrl;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSecureFiles
{
    public function secureFiles(): MorphMany
    {
        return $this->morphMany(SecureFile::class, 'attachable');
    }

    public function profilePhoto(): ?SecureFile
    {
        return $this->secureFile('profile_photo');
    }

    public function secureFile(string $category, string $variant = SecureFile::VARIANT_ORIGINAL): ?SecureFile
    {
        if ($this->relationLoaded('secureFiles')) {
            return $this->secureFiles
                ->where('category', $category)
                ->where('variant', $variant)
                ->sortByDesc('id')
                ->first();
        }

        return $this->secureFiles()
            ->where('category', $category)
            ->where('variant', $variant)
            ->latest('id')
            ->first();
    }

    public function secureFileWebUrl(string $category): ?string
    {
        $file = $this->secureFile($category);

        return $file === null ? null : SecureFileUrl::web($file);
    }

    public function secureFileWebDownloadUrl(string $category): ?string
    {
        $file = $this->secureFile($category);

        return $file === null ? null : SecureFileUrl::webDownload($file);
    }

    public function profilePhotoWebUrl(): ?string
    {
        $file = $this->secureFile('profile_photo');

        return $file === null ? null : SecureFileUrl::web($file);
    }

    public function profilePhotoApiUrl(): ?string
    {
        $file = $this->secureFile('profile_photo');

        return $file === null ? null : SecureFileUrl::api($file);
    }
}
