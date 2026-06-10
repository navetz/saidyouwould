<?php

namespace App\Contracts;

interface VideoStorage
{
    public function createUpload(string $filename, string $contentType, int $size): array;

    public function validateUploaded(string $storageKey, string $uploadToken): void;

    public function delete(string $storageKey): void;

    public function temporaryUrl(string $storageKey): string;
}
