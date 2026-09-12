<?php

namespace App\Http\Controllers;

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
        return $this->deliver($request, $uuid, FileAccessService::ACTION_VIEW, false);
    }

    public function download(Request $request, string $uuid): StreamedResponse
    {
        return $this->deliver($request, $uuid, FileAccessService::ACTION_DOWNLOAD, true);
    }

    private function deliver(Request $request, string $uuid, string $action, bool $forceDownload): StreamedResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(404);
        }

        $file = SecureFile::query()->where('uuid', $uuid)->first();
        if ($file === null || ! $this->access->allows($user, $file, $action)) {
            abort(404);
        }

        $auditAction = $forceDownload ? 'secure_file.downloaded' : 'secure_file.viewed';
        $this->activityLogger->logSecureFileEvent($auditAction, $file, $user, $request);

        return $this->responder->response($file, $forceDownload);
    }
}
