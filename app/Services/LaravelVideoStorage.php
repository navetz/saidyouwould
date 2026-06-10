<?php

namespace App\Services;

use App\Contracts\VideoStorage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LaravelVideoStorage implements VideoStorage
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('one-year-later.video_disk');
    }

    public function createUpload(string $filename, string $contentType, int $size): array
    {
        $extension = match ($contentType) {
            'video/mp4', 'application/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
            'video/x-m4v' => 'm4v',
        };
        $key = trim(config('one-year-later.video_directory'), '/')
            .'/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
        $expiresAt = now()->addMinutes(config('one-year-later.upload_link_minutes'));
        $upload = Storage::disk($this->disk)->temporaryUploadUrl($key, $expiresAt, [
            'ContentType' => $contentType,
        ]);

        $headers = [];
        foreach ($upload['headers'] as $name => $values) {
            if (! in_array(strtolower($name), ['host', 'content-length'], true)) {
                $headers[$name] = implode(', ', (array) $values);
            }
        }
        $headers['Content-Type'] = $contentType;

        return [
            'storage_key' => $key,
            'upload_url' => $upload['url'],
            'upload_headers' => $headers,
            'upload_token' => Crypt::encryptString(json_encode([
                'key' => $key,
                'content_type' => $contentType,
                'size' => $size,
                'expires_at' => $expiresAt->timestamp,
                'filename' => basename($filename),
            ], JSON_THROW_ON_ERROR)),
        ];
    }

    public function validateUploaded(string $storageKey, string $uploadToken): void
    {
        try {
            $receipt = json_decode(Crypt::decryptString($uploadToken), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw ValidationException::withMessages(['video' => 'The video upload could not be verified.']);
        }

        if (($receipt['key'] ?? null) !== $storageKey || ($receipt['expires_at'] ?? 0) < now()->timestamp) {
            throw ValidationException::withMessages(['video' => 'The video upload has expired or is invalid.']);
        }

        $disk = Storage::disk($this->disk);
        if (! $disk->exists($storageKey) || $disk->size($storageKey) !== (int) $receipt['size']) {
            throw ValidationException::withMessages(['video' => 'The video did not finish uploading.']);
        }

        $storedContentType = $disk->mimeType($storageKey);
        if ($storedContentType && $storedContentType !== $receipt['content_type']) {
            throw ValidationException::withMessages(['video' => 'The uploaded file type does not match the selected video.']);
        }
    }

    public function delete(string $storageKey): void
    {
        Storage::disk($this->disk)->delete($storageKey);
    }

    public function temporaryUrl(string $storageKey): string
    {
        abort_unless(Storage::disk($this->disk)->exists($storageKey), 404);

        return Storage::disk($this->disk)->temporaryUrl(
            $storageKey,
            now()->addMinutes(config('one-year-later.playback_link_minutes')),
            ['ResponseContentDisposition' => 'inline'],
        );
    }
}
