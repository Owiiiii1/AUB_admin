<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SecureFile;
use App\Services\ActivityLogger;
use App\Services\SecureFiles\FileAccessService;
use App\Services\SecureFiles\SecureFileResponder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileController extends Controller
{
    public function __construct(
        private readonly FileAccessService $access,
        private readonly SecureFileResponder $responder,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function show(Request $request, string $uuid): StreamedResponse
    {
        $user = $request->user();
        $file = SecureFile::query()->where('uuid', $uuid)->first();
        if ($file === null || $user === null || ! $this->access->allows($user, $file, FileAccessService::ACTION_VIEW)) {
            abort(404);
        }

        $this->activityLogger->logSecureFileEvent('secure_file.viewed', $file, $user, $request);

        return $this->responder->response($file, false);
    }
}
