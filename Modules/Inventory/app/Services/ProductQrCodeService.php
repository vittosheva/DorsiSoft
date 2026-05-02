<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Services\FileStoragePathService;
use Modules\Inventory\Enums\BarcodeTypeEnum;
use Modules\Inventory\Models\Product;

final class ProductQrCodeService
{
    public function sync(Product $product): void
    {
        if (! $this->shouldHaveQr($product)) {
            $this->deletePath($product->getOriginal('qr_code_path'));

            $product->qr_code_path = null;
            $product->qr_generated_at = null;

            return;
        }

        if (! $this->shouldRegenerate($product)) {
            return;
        }

        $path = $this->buildPath($product);

        Storage::disk($this->disk())->put($path, $this->renderSvg((string) $product->barcode));

        $this->deletePath($product->getOriginal('qr_code_path'), except: $path);

        $product->qr_code_path = $path;
        $product->qr_generated_at = now();
    }

    public function forget(Product $product): void
    {
        $this->deletePath($product->qr_code_path);
    }

    private function shouldHaveQr(Product $product): bool
    {
        return $product->barcode_type === BarcodeTypeEnum::Qr && filled($product->barcode);
    }

    private function shouldRegenerate(Product $product): bool
    {
        if (! $product->exists) {
            return true;
        }

        if ($product->isDirty(['barcode', 'barcode_type', 'company_id', 'code', 'name'])) {
            return true;
        }

        if (blank($product->qr_code_path)) {
            return true;
        }

        return ! Storage::disk($this->disk())->exists((string) $product->qr_code_path);
    }

    private function buildPath(Product $product): string
    {
        $reference = Str::slug((string) ($product->code ?: $product->name ?: 'product'));
        $payloadHash = mb_substr(sha1((string) $product->barcode), 0, 12);

        return FileStoragePathService::getPath(
            FileTypeEnum::InventoryQrCodes,
            $product,
            context: [
                'filename' => "{$reference}-{$payloadHash}.svg",
            ],
        );
    }

    private function buildPathOld(Product $product): string
    {
        $companyId = $product->company_id ?? 'shared';
        $reference = Str::slug((string) ($product->code ?: $product->name ?: 'product'));
        $payloadHash = mb_substr(sha1((string) $product->barcode), 0, 12);

        return sprintf(
            'inventory/products/qrcodes/%s/%s-%s.svg',
            $companyId,
            $reference,
            $payloadHash,
        );
    }

    private function renderSvg(string $barcode): string
    {
        $options = new QROptions([
            'outputBase64' => false,
            'outputType' => QROutputInterface::MARKUP_SVG,
            'eccLevel' => EccLevel::H,
            'scale' => 7,
            'addQuietzone' => true,
        ]);

        return (string) (new QRCode($options))->render($barcode);
    }

    private function disk(): string
    {
        return FileStoragePathService::getDisk(FileTypeEnum::InventoryQrCodes);
    }

    private function deletePath(?string $path, ?string $except = null): void
    {
        if (blank($path) || $path === $except) {
            return;
        }

        $disk = Storage::disk($this->disk());

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
