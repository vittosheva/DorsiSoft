<?php

declare(strict_types=1);

namespace Modules\Core\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Mayaram\LaravelOcr\Facades\LaravelOcr;
use Modules\People\Models\BusinessPartner;

final class ExtractOcrText implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public int $timeout = 120;

    public function __construct(
        public readonly int $businessPartnerId,
        public readonly string $relativePath,
        public readonly string $disk,
    ) {}

    public function handle(): void
    {
        $partner = BusinessPartner::withoutGlobalScopes()->find($this->businessPartnerId);

        if (! $partner instanceof BusinessPartner) {
            return;
        }

        $fullPath = Storage::disk($this->disk)->path($this->relativePath);

        /** @var Imagick $imagick */
        $imagick = new Imagick;
        $imagick->setResolution(300, 300);
        $imagick->readImage($fullPath);

        $pageCount = $imagick->getNumberImages();
        $pages = [];

        for ($i = 0; $i < $pageCount; $i++) {
            $imagick->setIteratorIndex($i);
            $page = $imagick->getImage();
            $page->setImageFormat('png');

            $tempPath = sys_get_temp_dir().'/ocr_page_'.$i.'_'.uniqid().'.png';
            $page->writeImage($tempPath);
            $page->destroy();

            $result = LaravelOcr::extract($tempPath);

            if (filled(mb_trim($result['text'] ?? ''))) {
                $pages[] = mb_trim($result['text']);
            }

            unlink($tempPath);
        }

        $imagick->clear();
        $imagick->destroy();

        $partner->update([
            'metadata->ocr_extracted_text' => implode("\n\n", $pages),
        ]);
    }
}
