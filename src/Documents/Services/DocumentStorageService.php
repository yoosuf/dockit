<?php

namespace Yoosuf\Document\Documents\Services;

use Yoosuf\Document\Documents\Exceptions\DocumentException;
use Yoosuf\Document\Documents\Support\ErrorCodes;
use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    /**
     * @return array{storage_path: string, size_bytes: int}
     */
    public function persist(string $sourcePath, string $filename): array
    {
        if (!is_file($sourcePath)) {
            throw new DocumentException(ErrorCodes::DOCUMENT_STORAGE_ERROR, 'Source artifact does not exist.', 500);
        }

        $relativePath = trim((string) config('document.output_dir', 'storage/app/generated'), '/');
        $relativePath = preg_replace('/^storage\/app\//', '', $relativePath ?? '') ?: 'generated';
        $storagePath = $relativePath.'/'.$filename;

        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw new DocumentException(ErrorCodes::DOCUMENT_STORAGE_ERROR, 'Failed to open artifact for storage.', 500);
        }

        $ok = Storage::disk('local')->put($storagePath, $stream);
        fclose($stream);

        if (!$ok) {
            throw new DocumentException(ErrorCodes::DOCUMENT_STORAGE_ERROR, 'Failed to store generated artifact.', 500);
        }

        return [
            'storage_path' => $storagePath,
            'size_bytes' => (int) Storage::disk('local')->size($storagePath),
        ];
    }

    public function absolutePath(string $storagePath): string
    {
        return Storage::disk('local')->path($storagePath);
    }

    public function delete(string $storagePath): void
    {
        Storage::disk('local')->delete($storagePath);
    }
}
