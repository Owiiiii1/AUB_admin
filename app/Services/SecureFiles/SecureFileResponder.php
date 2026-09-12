<?php

namespace App\Services\SecureFiles;

use App\Models\SecureFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileResponder
{
    public function response(SecureFile $file, bool $forceDownload = false): StreamedResponse
    {
        $disposition = (! $forceDownload && $file->inlineAllowed()) ? 'inline' : 'attachment';
        $filename = $this->asciiFilename($file);
        $utf8 = rawurlencode($file->original_name);
        $sensitive = $file->isSensitive();

        $headers = [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $sensitive ? 'private, no-store' : 'private, max-age=0, must-revalidate',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"; filename*=UTF-8\'\''.$utf8,
        ];

        $disk = Storage::disk($file->disk);
        $stream = $disk->readStream($file->path);
        if ($stream === false) {
            abort(404);
        }

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }

    private function asciiFilename(SecureFile $file): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->original_name) ?? '';
        $name = trim($name, '._');
        if ($name === '') {
            return 'file.'.$file->extension;
        }

        return $name;
    }
}
