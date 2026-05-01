<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Models\PdfShareLink;

final class PdfShareLinkService
{
    private const DEFAULT_EXPIRATION_DAYS = 7;

    public function create(Model&GeneratesPdf $document, int $createdBy, ?DateTimeInterface $expiresAt = null): PdfShareLink
    {
        if (! $document->exists) {
            throw new InvalidArgumentException(__('Cannot create a PDF share link for an unsaved document.'));
        }

        return PdfShareLink::query()
            ->create([
                'token' => (string) Str::uuid(),
                'shareable_type' => $document::class,
                'shareable_id' => $document->getKey(),
                'created_by' => $createdBy,
                'expires_at' => $expiresAt ?? now()->addDays(self::DEFAULT_EXPIRATION_DAYS),
            ]);
    }

    public function revoke(PdfShareLink $shareLink, ?int $revokedBy = null): PdfShareLink
    {
        if ($shareLink->isRevoked()) {
            return $shareLink;
        }

        $shareLink->forceFill([
            'revoked_by' => $revokedBy,
            'revoked_at' => now(),
        ])->save();

        return $shareLink->refresh();
    }

    public function temporarySignedUrl(PdfShareLink $shareLink): string
    {
        return $this->temporarySignedViewUrl($shareLink);
    }

    public function temporarySignedViewUrl(PdfShareLink $shareLink): string
    {
        return $this->temporarySignedRoute('core.pdf.share.view', $shareLink);
    }

    public function temporarySignedDownloadUrl(PdfShareLink $shareLink): string
    {
        return $this->temporarySignedRoute('core.pdf.share.download', $shareLink);
    }

    private function temporarySignedRoute(string $routeName, PdfShareLink $shareLink): string
    {
        return URL::temporarySignedRoute(
            $routeName,
            $shareLink->expires_at,
            ['pdfShareLink' => $shareLink->getRouteKey()],
        );
    }
}
