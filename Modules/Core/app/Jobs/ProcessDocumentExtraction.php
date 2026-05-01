<?php

declare(strict_types=1);

namespace Modules\Core\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Mayaram\LaravelOcr\Facades\LaravelOcr;
use Modules\Core\Services\CommercialDocumentSuggestionParser;
use Modules\Core\Services\DocumentExtractionStore;
use RuntimeException;
use Throwable;

final class ProcessDocumentExtraction implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public int $timeout = 120;

    public function __construct(public readonly int $processedDocumentId) {}

    public function handle(DocumentExtractionStore $store, CommercialDocumentSuggestionParser $parser): void
    {
        $document = $store->find($this->processedDocumentId);

        if ($document === null) {
            return;
        }

        $startedAt = microtime(true);
        $store->markProcessing($this->processedDocumentId);

        try {
            $disk = (string) data_get($document, 'extracted_data.disk');
            $relativePath = (string) data_get($document, 'extracted_data.path');

            if ($disk === '' || $relativePath === '') {
                throw new RuntimeException(__('The extraction source file is missing.'));
            }

            $rawText = $this->extractText($disk, $relativePath);
            $parsedPayload = $parser->parse($rawText);

            $store->markCompleted(
                $this->processedDocumentId,
                $rawText,
                $parsedPayload,
                microtime(true) - $startedAt,
            );
        } catch (Throwable $throwable) {
            report($throwable);

            $store->markFailed(
                $this->processedDocumentId,
                $throwable->getMessage(),
                microtime(true) - $startedAt,
            );
        }
    }

    private function extractText(string $disk, string $relativePath): string
    {
        if (! Storage::disk($disk)->exists($relativePath)) {
            throw new RuntimeException(__('The uploaded file could not be found.'));
        }

        $fullPath = Storage::disk($disk)->path($relativePath);

        if ($this->isPdf($relativePath)) {
            return $this->extractPdfText($fullPath);
        }

        return $this->extractImageText($fullPath);
    }

    private function extractImageText(string $fullPath): string
    {
        $result = LaravelOcr::extract($fullPath);

        return mb_trim((string) ($result['text'] ?? ''));
    }

    private function extractPdfText(string $fullPath): string
    {
        if (! class_exists('Imagick')) {
            throw new RuntimeException(__('Imagick is required to extract text from PDF files.'));
        }

        $imagickClass = 'Imagick';
        $imagick = new $imagickClass;
        $imagick->setResolution(300, 300);
        $imagick->readImage($fullPath);

        $pageCount = $imagick->getNumberImages();
        $pages = [];

        for ($index = 0; $index < $pageCount; $index++) {
            $imagick->setIteratorIndex($index);
            $page = $imagick->getImage();
            $page->setImageFormat('png');

            $tempPath = sprintf('%s/ocr_page_%s_%s.png', sys_get_temp_dir(), $index, uniqid('', true));

            try {
                $page->writeImage($tempPath);
                $result = LaravelOcr::extract($tempPath);
                $text = mb_trim((string) ($result['text'] ?? ''));

                if ($text !== '') {
                    $pages[] = $text;
                }
            } finally {
                $page->destroy();

                if (is_file($tempPath)) {
                    unlink($tempPath);
                }
            }
        }

        $imagick->clear();
        $imagick->destroy();

        return implode("\n\n", $pages);
    }

    private function isPdf(string $relativePath): bool
    {
        return str_ends_with(mb_strtolower($relativePath), '.pdf');
    }
}
