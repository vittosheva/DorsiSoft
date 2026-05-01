<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\Enums\FileTypeEnum;
use RuntimeException;

final class DocumentExtractionUploadStore
{
    /**
     * @return array{disk: string, path: string, original_filename: string}
     */
    public function persist(mixed $uploadedState): array
    {
        $disk = FileStoragePathService::getDisk(FileTypeEnum::DocumentExtractionUploads);
        $uploadedState = $this->normalizeState($uploadedState);

        if ($uploadedState instanceof UploadedFile) {
            return $this->persistUploadedFile($uploadedState, $disk);
        }

        $sourcePath = $this->normalizeStateToPath($uploadedState);

        if ($sourcePath === '') {
            throw new RuntimeException(__('Upload a supported file before starting extraction.'));
        }

        if ($this->isStoredPath($disk, $sourcePath)) {
            return [
                'disk' => $disk,
                'path' => $sourcePath,
                'original_filename' => basename($sourcePath),
            ];
        }

        if (! $this->isReadableAbsolutePath($sourcePath)) {
            throw new RuntimeException(__('The uploaded file could not be found.'));
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $filename = Str::ulid()->toBase32();
        $directory = FileStoragePathService::getPath(
            FileTypeEnum::DocumentExtractionUploads,
            context: ['record_id' => 'pending'],
        );
        $storedPath = $directory.'/'.$filename.($extension !== '' ? '.'.$extension : '');

        $stream = fopen($sourcePath, 'rb');

        if ($stream === false) {
            throw new RuntimeException(__('The uploaded file could not be opened for processing.'));
        }

        try {
            Storage::disk($disk)->put($storedPath, $stream);
        } finally {
            fclose($stream);
        }

        return [
            'disk' => $disk,
            'path' => $storedPath,
            'original_filename' => basename($sourcePath),
        ];
    }

    /**
     * @return array{disk: string, path: string, original_filename: string}
     */
    private function persistUploadedFile(UploadedFile $uploadedFile, string $disk): array
    {
        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = Str::ulid()->toBase32();
        $directory = FileStoragePathService::getPath(
            FileTypeEnum::DocumentExtractionUploads,
            context: ['record_id' => 'pending'],
        );
        $storedFilename = $filename.($extension !== '' ? '.'.$extension : '');
        $storedPath = $uploadedFile->storeAs($directory, $storedFilename, ['disk' => $disk]);

        if (! is_string($storedPath) || $storedPath === '') {
            throw new RuntimeException(__('The uploaded file could not be stored for processing.'));
        }

        return [
            'disk' => $disk,
            'path' => $storedPath,
            'original_filename' => $uploadedFile->getClientOriginalName(),
        ];
    }

    private function normalizeState(mixed $uploadedState): mixed
    {
        if (is_array($uploadedState)) {
            $uploadedState = reset($uploadedState);
        }

        return $uploadedState;
    }

    private function normalizeStateToPath(mixed $uploadedState): string
    {
        return is_string($uploadedState) ? mb_trim($uploadedState) : '';
    }

    private function isStoredPath(string $disk, string $path): bool
    {
        return ! $this->isAbsolutePath($path) && Storage::disk($disk)->exists($path);
    }

    private function isReadableAbsolutePath(string $path): bool
    {
        return $this->isAbsolutePath($path) && is_file($path) && is_readable($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR);
    }
}
