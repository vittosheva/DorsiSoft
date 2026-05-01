<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use DomainException;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Finance\Models\Collection;
use Modules\Sales\Models\Invoice;

/**
 * Genera borradores de asientos contables a partir de documentos del sistema.
 * Las cuentas debit/credit se resuelven desde DocumentType->default_*_account_code.
 */
final class JournalEntryFactory
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
    ) {}

    public function fromInvoice(Invoice $invoice): JournalEntry
    {
        $documentType = $invoice->documentType;

        if ($documentType === null || ! $documentType->affects_accounting) {
            throw new DomainException(
                __('Invoice [:code] has no accounting-enabled document type.', ['code' => $invoice->code])
            );
        }

        $period = $this->resolveOpenPeriod($invoice->company_id);

        $entry = $this->journalEntryService->createDraft([
            'fiscal_period_id' => $period->id,
            'description' => __('Invoice :code - :customer', [
                'code' => $invoice->code,
                'customer' => $invoice->customer_name,
            ]),
            'entry_date' => $invoice->issue_date->toDateString(),
            'source_type' => 'sales_invoice',
            'source_id' => $invoice->id,
        ]);

        $this->addAccountingLines(
            $entry,
            $invoice->company_id,
            $documentType->default_debit_account_code,
            $documentType->default_credit_account_code,
            (string) $invoice->total,
        );

        return $entry;
    }

    public function fromCollection(Collection $collection): JournalEntry
    {
        $period = $this->resolveOpenPeriod($collection->company_id);

        $entry = $this->journalEntryService->createDraft([
            'fiscal_period_id' => $period->id,
            'description' => __('Collection :code', ['code' => $collection->code]),
            'entry_date' => $collection->created_at->toDateString(),
            'source_type' => 'finance_collection',
            'source_id' => $collection->id,
        ]);

        return $entry;
    }

    private function resolveOpenPeriod(int $companyId): FiscalPeriod
    {
        $period = FiscalPeriod::query()
            ->where('company_id', $companyId)
            ->open()
            ->current()
            ->first();

        if ($period === null) {
            throw new DomainException(
                __('No open fiscal period found for the current date. Please open a period before posting.')
            );
        }

        return $period;
    }

    private function addAccountingLines(
        JournalEntry $entry,
        int $companyId,
        ?string $debitAccountCode,
        ?string $creditAccountCode,
        string $amount,
    ): void {
        if (blank($debitAccountCode) || blank($creditAccountCode)) {
            return;
        }

        $debitAccount = ChartOfAccount::query()
            ->where('company_id', $companyId)
            ->where('code', $debitAccountCode)
            ->first();

        $creditAccount = ChartOfAccount::query()
            ->where('company_id', $companyId)
            ->where('code', $creditAccountCode)
            ->first();

        if ($debitAccount === null || $creditAccount === null) {
            return;
        }

        $this->journalEntryService->addLine($entry, $debitAccount, $amount, '0');
        $this->journalEntryService->addLine($entry, $creditAccount, '0', $amount);
    }
}
