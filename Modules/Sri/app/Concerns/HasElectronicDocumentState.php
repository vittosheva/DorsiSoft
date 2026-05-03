<?php

declare(strict_types=1);

namespace Modules\Sri\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\DocumentStatus;
use Modules\Sri\Enums\ElectronicCorrectionStatusEnum;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Services\ElectronicDocumentCorrectionClassifier;

trait HasElectronicDocumentState
{
    public function getDisplayCommercialStatus(): mixed
    {
        if ($this->getElectronicStatus() === ElectronicStatusEnum::Authorized && ! $this->isCommerciallyVoided()) {
            return $this->getIssuedCommercialStatus() ?? $this->status;
        }

        return $this->status ?? null;
    }

    public function isElectronicDocumentMutable(): bool
    {
        return ($this->isCommerciallyEditable() && ! $this->hasElectronicProcessingLock())
            || $this->canEditRejectedElectronicDocumentInPlace();
    }

    public function canEditRejectedElectronicDocumentInPlace(): bool
    {
        return $this->supportsInPlaceRejectedElectronicCorrection()
            && $this->isCommerciallyIssued()
            && ! $this->hasElectronicProcessingLock()
            && $this->getElectronicStatus() === ElectronicStatusEnum::Rejected
            && $this->requiresElectronicCorrection()
            && blank($this->superseded_by_id ?? null)
            && ! $this->hasDownstreamElectronicCorrectionEffects();
    }

    public function canStartElectronicProcessing(): bool
    {
        return $this->isCommerciallyIssued()
            && in_array($this->getElectronicStatus(), [null, ElectronicStatusEnum::Pending], true);
    }

    public function canRetryElectronicProcessing(): bool
    {
        if (! $this->isCommerciallyIssued()) {
            return false;
        }

        return match ($this->getElectronicStatus()) {
            ElectronicStatusEnum::Error => true,
            ElectronicStatusEnum::Rejected => ! $this->requiresElectronicCorrection(),
            default => false,
        };
    }

    public function canProcessElectronicWorkflow(): bool
    {
        return $this->isCommerciallyIssued() && ! $this->hasElectronicProcessingLock();
    }

    public function hasElectronicProcessingLock(): bool
    {
        return in_array($this->getElectronicStatus(), [
            ElectronicStatusEnum::XmlGenerated,
            ElectronicStatusEnum::Signed,
            ElectronicStatusEnum::Submitted,
            ElectronicStatusEnum::Authorized,
        ], true);
    }

    public function canCorrectRejectedElectronicDocument(): bool
    {
        return $this->isCommerciallyIssued()
            && $this->getElectronicStatus() === ElectronicStatusEnum::Rejected
            && $this->requiresElectronicCorrection()
            && blank($this->superseded_by_id ?? null)
            && ! $this->canEditRejectedElectronicDocumentInPlace();
    }

    /**
     * @return array<string, mixed>
     */
    public function getInPlaceRejectedElectronicCorrectionAttributes(): array
    {
        return [
            'status' => $this->getDraftCommercialStatus(),
            'sequential_number' => null,
            'access_key' => null,
            'electronic_status' => null,
            'electronic_submitted_at' => null,
            'electronic_authorized_at' => null,
            'correction_status' => ElectronicCorrectionStatusEnum::InProgress,
            'correction_requested_at' => $this->correction_requested_at ?? now(),
            'corrected_at' => null,
            'correction_reason' => $this->correction_reason ?: app(ElectronicDocumentCorrectionClassifier::class)->summarize($this),
        ];
    }

    public function requiresElectronicCorrection(): bool
    {
        if ($this->getElectronicStatus() !== ElectronicStatusEnum::Rejected) {
            return false;
        }

        $correctionStatus = $this->getElectronicCorrectionStatus();

        if ($correctionStatus !== null) {
            return in_array($correctionStatus, [ElectronicCorrectionStatusEnum::Required, ElectronicCorrectionStatusEnum::Superseded], true);
        }

        return app(ElectronicDocumentCorrectionClassifier::class)->classifyDocument($this) === ElectronicDocumentCorrectionClassifier::REQUIRES_CORRECTION;
    }

    public function getElectronicCorrectionStatus(): ?ElectronicCorrectionStatusEnum
    {
        return $this->correction_status;
    }

    public function correctionSource(): BelongsTo
    {
        return $this->belongsTo(static::class, 'correction_source_id');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(static::class, 'superseded_by_id');
    }

    public function getDraftCommercialStatus(): mixed
    {
        $status = $this->status ?? null;

        if ($status instanceof BackedEnum) {
            $enumClass = $status::class;

            if (defined($enumClass.'::Draft')) {
                return constant($enumClass.'::Draft');
            }
        }

        $statusCast = $this->getCasts()['status'] ?? null;

        if (is_string($statusCast) && enum_exists($statusCast) && defined($statusCast.'::Draft')) {
            return constant($statusCast.'::Draft');
        }

        return 'draft';
    }

    public function syncCommercialStatusAfterAuthorization(?string $authorizedAt = null): array
    {
        $attributes = [];
        $issuedStatus = $this->getIssuedCommercialStatus();

        if ($issuedStatus !== null && ! $this->isCommerciallyVoided()
            && ($this->getCommercialStatusValue() === 'pending_authorization'
                || ! $this->isCommerciallyIssued())) {
            $attributes['status'] = $issuedStatus;
        }

        if (in_array('issue_date', $this->getFillable(), true)) {
            if (blank($this->issue_date ?? null)) {
                $attributes['issue_date'] = filled($authorizedAt) ? now()->parse($authorizedAt)->toDateString() : now()->toDateString();
            }
        }

        return $attributes;
    }

    protected function supportsInPlaceRejectedElectronicCorrection(): bool
    {
        return false;
    }

    protected function hasDownstreamElectronicCorrectionEffects(): bool
    {
        return false;
    }

    private function isCommerciallyEditable(): bool
    {
        $status = $this->status ?? null;

        if ($status instanceof DocumentStatus) {
            return $status->isEditable();
        }

        $statusValue = $this->getCommercialStatusValue();

        if ($statusValue !== null) {
            return $statusValue === 'draft';
        }

        return blank($this->issue_date ?? null) && blank($this->voided_at ?? null);
    }

    private function isCommerciallyIssued(): bool
    {
        $status = $this->status ?? null;

        if ($status instanceof DocumentStatus) {
            return ! $status->isEditable() && ! $status->isVoided();
        }

        $statusValue = $this->getCommercialStatusValue();

        if ($statusValue !== null) {
            return ! in_array($statusValue, ['draft', 'voided'], true);
        }

        return filled($this->issue_date ?? null) && blank($this->voided_at ?? null);
    }

    private function isCommerciallyVoided(): bool
    {
        $status = $this->status ?? null;

        if ($status instanceof DocumentStatus) {
            return $status->isVoided();
        }

        $statusValue = $this->getCommercialStatusValue();

        if ($statusValue !== null) {
            return $statusValue === 'voided';
        }

        return filled($this->voided_at ?? null);
    }

    private function getIssuedCommercialStatus(): mixed
    {
        $status = $this->status ?? null;

        if ($status instanceof BackedEnum) {
            $enumClass = $status::class;

            if (defined($enumClass.'::Issued')) {
                return constant($enumClass.'::Issued');
            }
        }

        $statusCast = $this->getCasts()['status'] ?? null;

        if (is_string($statusCast) && enum_exists($statusCast) && defined($statusCast.'::Issued')) {
            return constant($statusCast.'::Issued');
        }

        return 'issued';
    }

    private function getCommercialStatusValue(): ?string
    {
        $status = $this->status ?? null;

        if ($status instanceof BackedEnum) {
            return is_string($status->value) ? $status->value : (string) $status->value;
        }

        if (is_string($status)) {
            return $status;
        }

        return null;
    }
}
