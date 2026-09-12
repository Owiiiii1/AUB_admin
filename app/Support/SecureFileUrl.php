<?php

namespace App\Support;

use App\Models\SecureFile;

class SecureFileUrl
{
    public static function api(SecureFile $file): string
    {
        return url('/api/v1/files/'.$file->uuid);
    }

    public static function web(SecureFile $file): string
    {
        return url('/secure-files/'.$file->uuid);
    }

    public static function webDownload(SecureFile $file): string
    {
        return url('/secure-files/'.$file->uuid.'/download');
    }
}
