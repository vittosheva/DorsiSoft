<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Filament\Concerns\InteractsWithCreditNoteHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\CreditNoteResource;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\Invoice;
use Modules\Sri\Support\Concerns\InteractsWithElectronicAuditPanel;
use Modules\Sri\Support\Concerns\ShowsElectronicError;
use Modules\Workflow\Filament\CoreApp\Widgets\ApprovalHistoryWidget;

final class ViewCreditNote extends BaseViewRecord
{
    use InteractsWithCreditNoteHeaderActions;
    use InteractsWithElectronicAuditPanel;
    use InteractsWithSalesDocumentHeaderActions;
    use ShowsElectronicError;

    protected static string $resource = CreditNoteResource::class;

    protected function getFooterWidgets(): array
    {
        return [ApprovalHistoryWidget::class];
    }

    protected function getFooterWidgetData(): array
    {
        return $this->getElectronicAuditWidgetData();
    }

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            approvalActions: $this->getCreditNoteApprovalActions(),
            primaryActions: $this->getSalesDocumentPrimaryActions(),
            electronicActions: [
                $this->getCreditNoteIssueAction(),
                ...$this->getSalesDocumentElectronicActions(CreditNoteResource::class),
            ],
            managementActions: $this->getSalesDocumentManagementActions(
                duplicateAction: $this->getCreditNoteDuplicateAction(),
                extraActions: [
                    $this->getCreditNoteRegisterCollectionAction(),
                ],
            ),
        );
    }

    /**
     * @return array<int, string>
     */
    private function resolveApplicableInvoiceOptions(CreditNote $creditNote, ?string $search = null): array
    {
        return Invoice::query()
            ->select(['id', 'code', 'customer_name', 'total', 'paid_amount', 'credited_amount'])
            ->where('status', InvoiceStatusEnum::Issued)
            ->where('id', '!=', $creditNote->invoice_id)
            ->when(
                filled($creditNote->business_partner_id),
                fn ($q) => $q->where('business_partner_id', $creditNote->business_partner_id),
            )
            ->when(
                filled($search),
                fn ($q) => $q->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('code', 'like', mb_trim((string) $search).'%')
                        ->orWhere('customer_name', 'like', '%'.mb_trim((string) $search).'%');
                }),
            )
            ->orderBy('code')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn (Invoice $invoice): array => [
                $invoice->id => "{$invoice->code} — {$invoice->customer_name} (".
                    __('pending').': '.number_format(
                        (float) CollectionAllocationMath::pending(
                            (string) $invoice->total,
                            bcadd(
                                CollectionAllocationMath::normalize($invoice->paid_amount),
                                CollectionAllocationMath::normalize($invoice->credited_amount),
                                CollectionAllocationMath::SCALE
                            )
                        ),
                        2
                    ).')',
            ])
            ->all();
    }
}
