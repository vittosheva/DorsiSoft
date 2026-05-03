<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Contracts\DocumentContract;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Models\Traits\HasCustomerSnapshot;
use Modules\Core\Models\Traits\HasDocumentBehavior;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Models\Traits\HasYearlyAutoCode;
use Modules\Core\Support\Models\BaseModel;
use Modules\Finance\Models\Tax;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Casts\MoneyAmount;
use Modules\Sales\Contracts\HasSriSequential;
use Modules\Sales\Enums\DebitNoteStatusEnum;
use Modules\Sales\Enums\DocumentTypeEnum;
use Modules\Sales\Models\Traits\AutoAssignsDocumentType;
use Modules\Sri\Concerns\HasElectronicDocumentState;
use Modules\Sri\Concerns\HasElectronicEvents;
use Modules\Sri\Concerns\HasSriTechnicalExchanges;
use Modules\Sri\Contracts\GeneratesRidePdf;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicCorrectionStatusEnum;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\System\Models\DocumentType;

final class DebitNote extends BaseModel implements DocumentContract, GeneratesPdf, GeneratesRidePdf, HasElectronicBilling, HasSriSequential
{
    use AutoAssignsDocumentType;
    use HasCustomerSnapshot;
    use HasDocumentBehavior;
    use HasElectronicDocumentState;
    use HasElectronicEvents;
    use HasFactory;
    use HasSriTechnicalExchanges;
    use HasTenancy;
    use HasYearlyAutoCode;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sales_debit_notes';

    protected $fillable = [
        'company_id',
        'document_type_id',
        'code',
        'invoice_id',
        'business_partner_id',
        'customer_name',
        'customer_trade_name',
        'customer_identification_type',
        'customer_identification',
        'customer_address',
        'customer_email',
        'customer_phone',
        'currency_code',
        'reasons',
        'tax_id',
        'tax_name',
        'tax_rate',
        'subtotal',
        'tax_amount',
        'total',
        'status',
        'access_key',
        'establishment_code',
        'emission_point_code',
        'sequential_number',
        'ext_invoice_code',
        'ext_invoice_date',
        'ext_invoice_auth_number',
        'sri_payments',
        'payment_method',
        'payment_amount',
        'additional_info',
        'notes',
        'issue_date',
        'voided_at',
        'voided_reason',
        'metadata',
        'electronic_status',
        'electronic_submitted_at',
        'electronic_authorized_at',
        'correction_status',
        'correction_source_id',
        'superseded_by_id',
        'correction_requested_at',
        'corrected_at',
        'correction_reason',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DebitNoteStatusEnum::class,
            'electronic_status' => ElectronicStatusEnum::class,
            'electronic_submitted_at' => 'datetime',
            'electronic_authorized_at' => 'datetime',
            'correction_status' => ElectronicCorrectionStatusEnum::class,
            'correction_requested_at' => 'datetime',
            'corrected_at' => 'datetime',
            'motivos' => 'array',
            'additional_info' => 'array',
            'customer_email' => 'array',
            'metadata' => 'array',
            'sri_payments' => 'array',
            'subtotal' => MoneyAmount::class,
            'tax_amount' => MoneyAmount::class,
            'total' => MoneyAmount::class,
            'tax_rate' => MoneyAmount::class,
            'payment_amount' => MoneyAmount::class,
            'ext_invoice_date' => 'date',
            'issue_date' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'ND';
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function getSriSequentialCode(): ?string
    {
        if (blank($this->establishment_code) || blank($this->emission_point_code) || blank($this->sequential_number)) {
            return null;
        }

        return "{$this->establishment_code}-{$this->emission_point_code}-{$this->sequential_number}";
    }

    public function getSriDocumentType(): DocumentTypeEnum
    {
        return DocumentTypeEnum::DebitNote;
    }

    // HasElectronicBilling implementation

    public function getSriDocumentTypeCode(): string
    {
        return '05';
    }

    public function getElectronicStatus(): ?ElectronicStatusEnum
    {
        return $this->electronic_status;
    }

    public function getAccessKey(): ?string
    {
        return $this->access_key;
    }

    public function getElectronicEagerLoads(): array
    {
        return ['company', 'invoice:id,establishment_code,emission_point_code,sequential_number,issue_date'];
    }

    public function getXmlStoragePath(string $tenantRuc): string
    {
        $year = $this->issue_date?->year ?? now()->year;

        return "tenants/{$tenantRuc}/documents/xml/debit-notes/{$year}/{$this->access_key}.xml";
    }

    public function getRideStoragePath(string $tenantRuc): string
    {
        $year = $this->issue_date?->year ?? now()->year;

        return "tenants/{$tenantRuc}/documents/ride/debit-notes/{$year}/{$this->access_key}.xml";
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function getTotalReasonAmount(): string
    {
        $reasons = $this->reasons ?? [];

        return (string) collect($reasons)->sum(fn (array $reason) => (float) ($reason['value'] ?? 0));
    }

    /**
     * @return list<array{method: string, amount: string}>
     */
    public function getResolvedSriPayments(): array
    {
        // Siempre usar 2 decimales para pagos y total, igual que en el XML
        $total = number_format((float) ($this->total ?? 0), 2, '.', '');
        $payments = collect($this->sri_payments ?? [])
            ->filter(static fn (mixed $payment): bool => is_array($payment) && filled($payment['method'] ?? null))
            ->map(static fn (array $payment): array => [
                'method' => (string) $payment['method'],
                'amount' => number_format((float) ($payment['amount'] ?? 0), 2, '.', ''),
            ])
            ->values()
            ->all();

        // Si hay pagos, forzar que la suma sea igual al total (ajustar el último pago si es necesario)
        if (count($payments) > 0) {
            $sum = array_sum(array_map(fn ($p) => (float) $p['amount'], $payments));
            $diff = round((float) $total - $sum, 2);
            if (abs($diff) > 0 && count($payments) > 0) {
                // Ajustar el último pago para cuadrar exactamente
                $payments[count($payments) - 1]['amount'] = number_format((float) $payments[count($payments) - 1]['amount'] + $diff, 2, '.', '');
            }

            return $payments;
        }

        if (blank($this->payment_method)) {
            return [];
        }

        return [[
            'method' => (string) $this->payment_method,
            'amount' => $total,
        ]];
    }

    /**
     * @return array{method: string, amount: string}|null
     */
    public function getPrimarySriPayment(): ?array
    {
        return $this->getResolvedSriPayments()[0] ?? null;
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DebitNoteItem::class, 'debit_note_id')->orderBy('sort_order');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    public function getPdfView(): string
    {
        return 'sales::pdf.v1.sri.debit-note';
    }

    public function getPdfFileType(): FileTypeEnum
    {
        return FileTypeEnum::DebitNotePdf;
    }

    public function getPdfEagerLoads(): array
    {
        return ['invoice:id,code,establishment_code,emission_point_code,sequential_number,issue_date', 'company', 'creator:id,name'];
    }

    public function getPdfViewData(): array
    {
        return [
            'taxBreakdown' => [
                [
                    'label' => filled($this->tax_name)
                        ? sprintf('%s (%s%%)', $this->tax_name, mb_rtrim(mb_rtrim(number_format((float) ($this->tax_rate ?? 0), 2, '.', ''), '0'), '.'))
                        : __('Tax'),
                    'base_amount' => number_format((float) ($this->subtotal ?? 0), 4, '.', ''),
                    'tax_amount' => number_format((float) ($this->tax_amount ?? 0), 4, '.', ''),
                ],
            ],
            'totalDiscountAmount' => '0.0000',
        ];
    }

    public function getPdfDownloadFilename(): string
    {
        return str($this->code)->slug()->append('.pdf')->toString();
    }

    // ─── GeneratesRidePdf ───────────────────────────────────────────────────

    public function getRidePdfView(): string
    {
        return 'sales::pdf.v2.ride-debit-note';
    }

    public function getRidePdfType(): string
    {
        return 'debit-note';
    }

    public function getRidePdfStoragePath(string $tenantRuc): string
    {
        $year = $this->issue_date?->year ?? now()->year;

        return "tenants/{$tenantRuc}/pdfs/v2/ride/debit-notes/{$year}/{$this->access_key}.pdf";
    }

    public function getRidePdfStorageDisk(): string
    {
        return 'local';
    }

    public function getRidePdfEagerLoads(): array
    {
        return ['invoice:id,code,establishment_code,emission_point_code,sequential_number,issue_date', 'company', 'creator:id,name'];
    }

    public function getRidePdfViewData(): array
    {
        return [];
    }

    public function getRidePdfDownloadFilename(): string
    {
        return str($this->code)->slug()->append('-ride.pdf')->toString();
    }

    public function getRideSriDocumentTypeLabel(): string
    {
        return 'NOTA DE DÉBITO';
    }

    /**
     * Application-facing alias. Persist to the legacy motivos JSON column.
     */
    protected function reasons(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): array => self::normalizeReasonsPayload($attributes['motivos'] ?? []),
            set: fn (mixed $value): array => ['motivos' => json_encode(self::normalizeReasonsPayload($value), JSON_UNESCAPED_UNICODE)],
        );
    }

    /**
     * @return array<int, array{reason?: string, value?: mixed}>
     */
    private static function normalizeReasonsPayload(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn (mixed $reason): bool => is_array($reason))
            ->map(fn (array $reason): array => [
                'reason' => $reason['reason'] ?? '',
                'value' => $reason['value'] ?? 0,
            ])
            ->values()
            ->all();
    }
}
