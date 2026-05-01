<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Models\Company;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Enums\DebitNoteStatusEnum;
use Modules\Sales\Enums\DeliveryGuideStatusEnum;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Enums\PurchaseSettlementStatusEnum;
use Modules\Sales\Enums\WithholdingStatusEnum;
use Modules\Sales\Events\CreditNoteIssued;
use Modules\Sales\Events\DebitNoteIssued;
use Modules\Sales\Events\DeliveryGuideIssued;
use Modules\Sales\Events\InvoiceIssued;
use Modules\Sales\Events\PurchaseSettlementIssued;
use Modules\Sales\Events\WithholdingIssued;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\DeliveryGuide;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Models\Withholding;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Services\DocumentSequentialService;
use Modules\Sri\Services\SriDocumentPreValidator;

/**
 * Orquestador de emisión de documentos SRI.
 *
 * Centraliza la secuencia: validar → actualizar estado → registrar secuencial → disparar evento.
 * Las validaciones específicas de negocio (límite de NC, aprobaciones) son responsabilidad del caller.
 *
 * Todos los métodos abren su propia transacción.
 */
final class DocumentIssuanceService
{
    public function __construct(
        private readonly DocumentSequentialService $sequentialService,
        private readonly DebitNoteTotalsCalculator $debitNoteTotalsCalculator,
        private readonly SriDocumentPreValidator $preValidator,
    ) {}

    /**
     * Emite una factura: transiciona a Issued, registra el secuencial y despacha InvoiceIssued.
     *
     * @throws InvalidArgumentException Si la factura no está en estado Draft
     */
    public function issueInvoice(Invoice $invoice, ?int $performedBy = null): void
    {
        if (! $invoice->status->canTransitionTo(InvoiceStatusEnum::Issued)) {
            throw new InvalidArgumentException(
                __('Cannot issue an invoice with status :status.', ['status' => $invoice->status->getLabel()])
            );
        }

        $this->validateElectronicIssuance($invoice);

        DB::transaction(function () use ($invoice, $performedBy): void {
            $invoice->status = InvoiceStatusEnum::Issued;
            $invoice->issue_date ??= now()->toDateString();
            $invoice->save();

            $this->recordSequential($invoice->company_id, $invoice->establishment_code, $invoice->emission_point_code, $invoice->sequential_number, SriDocumentTypeEnum::Invoice, $performedBy);
        });

        InvoiceIssued::dispatch($invoice);
    }

    /**
     * Emite una nota de crédito: transiciona a Issued, registra el secuencial y despacha CreditNoteIssued.
     *
     * @throws InvalidArgumentException Si la NC no está en estado Draft
     */
    public function issueCreditNote(CreditNote $creditNote, ?int $performedBy = null): void
    {
        if (! $creditNote->status->canTransitionTo(CreditNoteStatusEnum::Issued)) {
            throw new InvalidArgumentException(
                __('Cannot issue a credit note with status :status.', ['status' => $creditNote->status->getLabel()])
            );
        }

        $this->validateElectronicIssuance($creditNote);

        DB::transaction(function () use ($creditNote, $performedBy): void {
            $creditNote->status = CreditNoteStatusEnum::Issued;
            $creditNote->issue_date ??= now()->toDateString();
            $creditNote->save();

            $this->recordSequential($creditNote->company_id, $creditNote->establishment_code, $creditNote->emission_point_code, $creditNote->sequential_number, SriDocumentTypeEnum::CreditNote, $performedBy);
        });

        CreditNoteIssued::dispatch($creditNote);
    }

    /**
     * Emite una nota de débito: recalcula totales, transiciona a Issued, registra secuencial y despacha DebitNoteIssued.
     *
     * @throws InvalidArgumentException Si la ND no está en Draft, no tiene reasons, o el total es cero
     */
    public function issueDebitNote(DebitNote $debitNote, ?int $performedBy = null): void
    {
        if (! $debitNote->status->canTransitionTo(DebitNoteStatusEnum::Issued)) {
            throw new InvalidArgumentException(
                __('Cannot issue a debit note with status :status.', ['status' => $debitNote->status->getLabel()])
            );
        }

        $this->debitNoteTotalsCalculator->recalculate($debitNote);

        if (empty($debitNote->reasons ?? [])) {
            throw new InvalidArgumentException(__('Add at least one reason to the debit note before issuing it.'));
        }

        if ((float) $debitNote->total <= 0) {
            throw new InvalidArgumentException(__('The debit note total must be greater than zero.'));
        }

        $this->assertDebitNotePaymentsMatchTotal($debitNote);
        $this->validateElectronicIssuance($debitNote);

        DB::transaction(function () use ($debitNote, $performedBy): void {
            $debitNote->status = DebitNoteStatusEnum::Issued;
            $debitNote->issue_date ??= now()->toDateString();
            $debitNote->save();

            $this->recordSequential($debitNote->company_id, $debitNote->establishment_code, $debitNote->emission_point_code, $debitNote->sequential_number, SriDocumentTypeEnum::DebitNote, $performedBy);
        });

        DebitNoteIssued::dispatch($debitNote);
    }

    /**
     * Emite una guía de remisión: transiciona a Issued, registra el secuencial y despacha el evento.
     *
     * @throws InvalidArgumentException Si la guía no está en Draft o no tiene destinatarios
     */
    public function issueDeliveryGuide(DeliveryGuide $deliveryGuide, ?int $performedBy = null): void
    {
        if (! $deliveryGuide->status->canTransitionTo(DeliveryGuideStatusEnum::Issued)) {
            throw new InvalidArgumentException(
                __('Cannot issue a delivery guide with status :status.', ['status' => $deliveryGuide->status->getLabel()])
            );
        }

        if ($deliveryGuide->recipients()->doesntExist()) {
            throw new InvalidArgumentException(__('Add at least one recipient to the delivery guide before issuing it.'));
        }

        $this->validateElectronicIssuance($deliveryGuide);

        DB::transaction(function () use ($deliveryGuide, $performedBy): void {
            $deliveryGuide->status = DeliveryGuideStatusEnum::Issued;
            $deliveryGuide->issue_date ??= now()->toDateString();
            $deliveryGuide->save();

            $this->recordSequential($deliveryGuide->company_id, $deliveryGuide->establishment_code, $deliveryGuide->emission_point_code, $deliveryGuide->sequential_number, SriDocumentTypeEnum::DeliveryGuide, $performedBy);
        });

        DeliveryGuideIssued::dispatch($deliveryGuide);
    }

    /**
     * Emite una liquidación de compra: transiciona a Issued, registra el secuencial y despacha PurchaseSettlementIssued.
     *
     * @throws InvalidArgumentException Si la liquidación no está en Draft o no tiene ítems
     */
    public function issuePurchaseSettlement(PurchaseSettlement $settlement, ?int $performedBy = null): void
    {
        if (! $settlement->status->canTransitionTo(PurchaseSettlementStatusEnum::Issued)) {
            throw new InvalidArgumentException(
                __('Cannot issue a purchase settlement with status :status.', ['status' => $settlement->status->getLabel()])
            );
        }

        if ($settlement->items()->doesntExist()) {
            throw new InvalidArgumentException(__('Add at least one item to the purchase settlement before issuing it.'));
        }

        $this->validateElectronicIssuance($settlement);

        DB::transaction(function () use ($settlement, $performedBy): void {
            $settlement->status = PurchaseSettlementStatusEnum::Issued;
            $settlement->issue_date ??= now()->toDateString();
            $settlement->save();

            $this->recordSequential($settlement->company_id, $settlement->establishment_code, $settlement->emission_point_code, $settlement->sequential_number, SriDocumentTypeEnum::PurchaseSettlement, $performedBy);
        });

        PurchaseSettlementIssued::dispatch($settlement);
    }

    /**
     * Emite una retención: transiciona a Issued, registra el secuencial y despacha WithholdingIssued.
     *
     * @throws InvalidArgumentException Si la retención no está en Draft o no tiene ítems
     */
    public function issueWithholding(Withholding $withholding, ?int $performedBy = null): void
    {
        if (! $withholding->status->canTransitionTo(WithholdingStatusEnum::Issued)) {
            throw new InvalidArgumentException(
                __('Cannot issue a withholding with status :status.', ['status' => $withholding->status->getLabel()])
            );
        }

        if ($withholding->items()->doesntExist()) {
            throw new InvalidArgumentException(__('Add at least one withholding item before issuing.'));
        }

        $this->validateElectronicIssuance($withholding);

        DB::transaction(function () use ($withholding, $performedBy): void {
            $withholding->status = WithholdingStatusEnum::Issued;
            $withholding->issue_date ??= now()->toDateString();
            $withholding->save();

            $this->recordSequential($withholding->company_id, $withholding->establishment_code, $withholding->emission_point_code, $withholding->sequential_number, SriDocumentTypeEnum::Withholding, $performedBy);
        });

        WithholdingIssued::dispatch($withholding);
    }

    private function assertDebitNotePaymentsMatchTotal(DebitNote $debitNote): void
    {
        $payments = $debitNote->getResolvedSriPayments();

        if ($payments === []) {
            throw new InvalidArgumentException(__('Add at least one payment method before issuing the debit note.'));
        }

        $paymentsTotal = number_format(
            collect($payments)->sum(fn (array $payment): float => (float) ($payment['amount'] ?? 0)),
            4,
            '.',
            '',
        );

        if (bccomp($paymentsTotal, (string) $debitNote->total, 4) !== 0) {
            throw new InvalidArgumentException(__('The sum of debit note payments must equal the document total.'));
        }
    }

    private function recordSequential(
        int $companyId,
        ?string $establishmentCode,
        ?string $emissionPointCode,
        ?string $sequentialNumber,
        SriDocumentTypeEnum $documentType,
        ?int $performedBy,
    ): void {
        if (filled($establishmentCode) && filled($emissionPointCode) && filled($sequentialNumber)) {
            $this->sequentialService->record(
                $companyId,
                $establishmentCode,
                $emissionPointCode,
                $sequentialNumber,
                $documentType,
                $performedBy,
            );
        }
    }

    private function validateElectronicIssuance(HasElectronicBilling $document): void
    {
        /** @var Model&HasElectronicBilling $document */
        $company = Company::withoutGlobalScopes()->find($document->company_id);

        if (! $company instanceof Company) {
            throw new InvalidArgumentException(__('Cannot issue a document without a valid company assigned.'));
        }

        $document->issue_date ??= now()->toDateString();
        $document->loadMissing($document->getElectronicEagerLoads());

        $this->preValidator->validate($document, $company);
    }
}
