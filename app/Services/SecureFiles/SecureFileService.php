<?php

namespace App\Services\SecureFiles;

use App\Exceptions\SecureFileException;
use App\Models\SecureFile;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SecureFileService
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN_MIME_TYPES = [
        'image/svg+xml',
        'text/html',
        'text/javascript',
        'application/javascript',
        'application/x-javascript',
        'text/x-php',
        'application/x-php',
        'application/x-httpd-php',
        'application/x-executable',
        'application/x-msdownload',
        'application/x-sh',
        'application/x-bat',
        'application/zip',
        'application/x-zip-compressed',
    ];

    public function __construct(
        private readonly ImageNormalizer $images,
        private readonly FileSecurityScanner $scanner,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function store(
        Model $attachable,
        string $category,
        UploadedFile $upload,
        ?User $uploader,
        ?Request $request = null,
    ): SecureFile {
        $binary = $this->readUpload($upload);
        $originalName = (string) $upload->getClientOriginalName();

        return $this->storeBinary($attachable, $category, $binary, $originalName, $uploader, $request, 'secure_file.uploaded');
    }

    public function storeBinary(
        Model $attachable,
        string $category,
        string $binary,
        string $originalName,
        ?User $uploader,
        ?Request $request = null,
        string $auditAction = 'secure_file.uploaded',
    ): SecureFile {
        $config = $this->categoryOrFail($category);
        $this->assertAttachableAllowed($attachable, $config);

        $prepared = $this->prepareObject($category, $config, $binary, $originalName);
        $file = $this->persist($attachable, $category, $prepared, $uploader);

        if (($config['singleton'] ?? false) === true) {
            $this->retirePrevious($attachable, $category, $file);
        }

        $this->audit($auditAction, $file, $uploader, $request);

        return $file;
    }

    public function importExisting(
        Model $attachable,
        string $category,
        string $binary,
        string $originalName,
        ?User $uploader = null,
        ?Request $request = null,
    ): SecureFile {
        $existing = $this->currentOriginal($attachable, $category);
        if ($existing !== null) {
            return $existing;
        }

        return $this->storeBinary(
            $attachable,
            $category,
            $binary,
            $originalName,
            $uploader,
            $request,
            'secure_file.uploaded',
        );
    }

    public function replace(
        Model $attachable,
        string $category,
        UploadedFile $upload,
        ?User $uploader,
        ?Request $request = null,
    ): SecureFile {
        $binary = $this->readUpload($upload);
        $originalName = (string) $upload->getClientOriginalName();
        $action = $this->currentOriginal($attachable, $category) !== null
            ? 'secure_file.replaced'
            : 'secure_file.uploaded';

        return $this->storeBinary($attachable, $category, $binary, $originalName, $uploader, $request, $action);
    }

    public function delete(SecureFile $file, ?User $actor = null, ?Request $request = null): void
    {
        DB::transaction(function () use ($file): void {
            $file->variants()->get()->each(function (SecureFile $variant): void {
                $variant->delete();
            });
            $file->delete();
        });

        $this->audit('secure_file.deleted', $file, $actor, $request);
    }

    public function deleteAllFor(Model $attachable, ?User $actor = null, ?Request $request = null): void
    {
        $files = $attachable->secureFiles()
            ->where('variant', SecureFile::VARIANT_ORIGINAL)
            ->get();

        foreach ($files as $file) {
            $this->delete($file, $actor, $request);
        }
    }

    public function purge(SecureFile $file): void
    {
        $disk = Storage::disk($file->disk);
        foreach ($file->variants()->withTrashed()->get() as $variant) {
            $disk->delete($variant->path);
            $variant->forceDelete();
        }

        $disk->delete($file->path);
        $file->forceDelete();
    }

    public function absolutePath(SecureFile $file): string
    {
        return Storage::disk($file->disk)->path($file->path);
    }

    /**
     * @return resource
     */
    public function open(SecureFile $file)
    {
        $stream = Storage::disk($file->disk)->readStream($file->path);
        if ($stream === false) {
            throw new RuntimeException('Unable to open stored file.');
        }

        return $stream;
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryOrFail(string $category): array
    {
        $config = config('aub-files.categories.'.$category);
        if (! is_array($config)) {
            throw SecureFileException::unknownCategory($category);
        }

        return $config;
    }

    private function currentOriginal(Model $attachable, string $category): ?SecureFile
    {
        return $attachable->secureFiles()
            ->where('category', $category)
            ->where('variant', SecureFile::VARIANT_ORIGINAL)
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{original: array<string, mixed>, thumbnail: array<string, mixed>|null}
     */
    private function prepareObject(string $category, array $config, string $binary, string $originalName): array
    {
        if ($binary === '') {
            throw SecureFileException::invalidType();
        }

        $max = (int) ($config['max_size_bytes'] ?? 0);
        if ($max > 0 && strlen($binary) > $max) {
            throw SecureFileException::tooLarge();
        }

        $detected = $this->detectMime($binary);
        $allowed = $config['allowed_mime_types'] ?? [];
        if (! in_array($detected, $allowed, true) || in_array($detected, self::FORBIDDEN_MIME_TYPES, true)) {
            throw SecureFileException::invalidType();
        }

        $isImage = in_array($detected, ['image/jpeg', 'image/png', 'image/webp'], true);
        $body = $binary;
        $mime = $detected;
        $extension = $this->extensionForMime($detected);

        if ($isImage) {
            [$body, $mime, $extension] = $this->images->normalize($binary, $detected);
        }

        $tmp = $this->writeTemp($body);
        try {
            $this->scanner->scan($tmp, $mime);
        } finally {
            @unlink($tmp);
        }

        $uuid = (string) Str::uuid();
        $storedName = $uuid.'.'.$extension;
        $path = 'objects/'.substr($uuid, 0, 2).'/'.$uuid;

        $original = [
            'uuid' => $uuid,
            'variant' => SecureFile::VARIANT_ORIGINAL,
            'parent_id' => null,
            'path' => $path,
            'original_name' => $this->safeOriginalName($originalName, $extension),
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => strlen($body),
            'sha256' => hash('sha256', $body),
            'contents' => $body,
        ];

        $thumbnail = null;
        if ($isImage && ($config['thumbnail_allowed'] ?? false) === true) {
            $maxEdge = (int) config('aub-files.thumbnail.max_edge', 256);
            [$thumbBody, $thumbMime, $thumbExt] = $this->images->thumbnail($body, $maxEdge);
            $thumbUuid = (string) Str::uuid();
            $thumbnail = [
                'uuid' => $thumbUuid,
                'variant' => SecureFile::VARIANT_THUMBNAIL,
                'path' => 'objects/'.substr($thumbUuid, 0, 2).'/'.$thumbUuid,
                'original_name' => 'thumbnail.'.$thumbExt,
                'stored_name' => $thumbUuid.'.'.$thumbExt,
                'mime_type' => $thumbMime,
                'extension' => $thumbExt,
                'size_bytes' => strlen($thumbBody),
                'sha256' => hash('sha256', $thumbBody),
                'contents' => $thumbBody,
            ];
        }

        return ['original' => $original, 'thumbnail' => $thumbnail];
    }

    /**
     * @param  array{original: array<string, mixed>, thumbnail: array<string, mixed>|null}  $prepared
     */
    private function persist(Model $attachable, string $category, array $prepared, ?User $uploader): SecureFile
    {
        $disk = (string) config('aub-files.disk', 'aub_private');
        $written = [];

        try {
            $originalMeta = $prepared['original'];
            Storage::disk($disk)->put($originalMeta['path'], $originalMeta['contents']);
            $written[] = $originalMeta['path'];
            $this->assertStoredHash($disk, $originalMeta['path'], $originalMeta['sha256']);

            $thumbMeta = $prepared['thumbnail'];
            if ($thumbMeta !== null) {
                Storage::disk($disk)->put($thumbMeta['path'], $thumbMeta['contents']);
                $written[] = $thumbMeta['path'];
                $this->assertStoredHash($disk, $thumbMeta['path'], $thumbMeta['sha256']);
            }

            return DB::transaction(function () use ($attachable, $category, $disk, $originalMeta, $thumbMeta, $uploader): SecureFile {
                $original = SecureFile::query()->create([
                    'uuid' => $originalMeta['uuid'],
                    'attachable_type' => $attachable->getMorphClass(),
                    'attachable_id' => $attachable->getKey(),
                    'category' => $category,
                    'variant' => SecureFile::VARIANT_ORIGINAL,
                    'parent_id' => null,
                    'disk' => $disk,
                    'path' => $originalMeta['path'],
                    'original_name' => $originalMeta['original_name'],
                    'stored_name' => $originalMeta['stored_name'],
                    'mime_type' => $originalMeta['mime_type'],
                    'extension' => $originalMeta['extension'],
                    'size_bytes' => $originalMeta['size_bytes'],
                    'sha256' => $originalMeta['sha256'],
                    'uploaded_by' => $uploader?->id,
                ]);

                if ($thumbMeta !== null) {
                    SecureFile::query()->create([
                        'uuid' => $thumbMeta['uuid'],
                        'attachable_type' => $attachable->getMorphClass(),
                        'attachable_id' => $attachable->getKey(),
                        'category' => $category,
                        'variant' => SecureFile::VARIANT_THUMBNAIL,
                        'parent_id' => $original->id,
                        'disk' => $disk,
                        'path' => $thumbMeta['path'],
                        'original_name' => $thumbMeta['original_name'],
                        'stored_name' => $thumbMeta['stored_name'],
                        'mime_type' => $thumbMeta['mime_type'],
                        'extension' => $thumbMeta['extension'],
                        'size_bytes' => $thumbMeta['size_bytes'],
                        'sha256' => $thumbMeta['sha256'],
                        'uploaded_by' => $uploader?->id,
                    ]);
                }

                return $original;
            });
        } catch (\Throwable $e) {
            foreach ($written as $path) {
                Storage::disk($disk)->delete($path);
            }

            throw $e;
        }
    }

    private function retirePrevious(Model $attachable, string $category, SecureFile $keep): void
    {
        $previous = $attachable->secureFiles()
            ->where('category', $category)
            ->where('variant', SecureFile::VARIANT_ORIGINAL)
            ->where('id', '!=', $keep->id)
            ->get();

        foreach ($previous as $old) {
            $old->variants()->get()->each(fn (SecureFile $variant) => $variant->delete());
            $old->delete();
        }
    }

    private function detectMime(string $binary): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary);

        return is_string($mime) ? strtolower($mime) : 'application/octet-stream';
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }

    private function safeOriginalName(string $name, string $extension): string
    {
        $base = basename(str_replace(['\\', "\0"], '/', $name));
        $base = preg_replace('/[^\p{L}\p{N}\.\-_ ]+/u', '_', $base) ?? 'file';
        $base = trim($base);
        if ($base === '' || $base === '.' || $base === '..') {
            $base = 'file.'.$extension;
        }

        return mb_substr($base, 0, 180);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function assertAttachableAllowed(Model $attachable, array $config): void
    {
        $allowed = $config['attachable_types'] ?? [];
        $alias = $attachable->getMorphClass();
        if (! in_array($alias, $allowed, true)) {
            throw SecureFileException::attachableNotAllowed();
        }
    }

    private function assertStoredHash(string $disk, string $path, string $expected): void
    {
        $stored = Storage::disk($disk)->get($path);
        if (! is_string($stored) || hash('sha256', $stored) !== $expected) {
            throw new RuntimeException('Stored file hash mismatch.');
        }
    }

    private function readUpload(UploadedFile $upload): string
    {
        $path = $upload->getRealPath();
        if (! is_string($path) || $path === '') {
            throw SecureFileException::invalidType();
        }

        $binary = file_get_contents($path);
        if (! is_string($binary)) {
            throw SecureFileException::invalidType();
        }

        return $binary;
    }

    private function writeTemp(string $binary): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'aubsf');
        if ($tmp === false) {
            throw new RuntimeException('Unable to allocate temp file.');
        }
        file_put_contents($tmp, $binary);

        return $tmp;
    }

    private function audit(string $action, SecureFile $file, ?User $actor, ?Request $request): void
    {
        $this->activityLogger->logSecureFileEvent($action, $file, $actor, $request);
    }
}
