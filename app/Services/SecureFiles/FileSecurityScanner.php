<?php

namespace App\Services\SecureFiles;

interface FileSecurityScanner
{
    /**
     * Hook for future malware scanning. Must throw on reject.
     */
    public function scan(string $absolutePath, string $mimeType): void;
}
