<?php

namespace App\Services\SecureFiles;

/**
 * Mandatory MIME/size/image checks live in SecureFileService.
 * This no-op is the extension point for ClamAV or similar later.
 */
class NullFileSecurityScanner implements FileSecurityScanner
{
    public function scan(string $absolutePath, string $mimeType): void
    {
    }
}
