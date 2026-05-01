<?php

declare(strict_types=1);

namespace Modules\Accounting\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Accounting\Services\JournalEntryFactory;
use Modules\Sales\Events\InvoiceIssued;
use Throwable;

final class CreateDraftJournalEntryListener
{
    public function __construct(
        private readonly JournalEntryFactory $factory,
    ) {}

    public function handle(InvoiceIssued $event): void
    {
        $invoice = $event->invoice;

        if (! $invoice->documentType?->affects_accounting) {
            return;
        }

        try {
            $this->factory->fromInvoice($invoice);
        } catch (Throwable $e) {
            Log::warning('Could not create draft journal entry for invoice [:code]: :message', [
                'code' => $invoice->code,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
