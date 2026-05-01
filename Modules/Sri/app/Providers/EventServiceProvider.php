<?php

declare(strict_types=1);

namespace Modules\Sri\Providers;

use Modules\Core\Providers\BaseModuleEventServiceProvider;
use Modules\Sales\Events\CreditNoteIssued;
use Modules\Sales\Events\DebitNoteIssued;
use Modules\Sales\Events\DeliveryGuideIssued;
use Modules\Sales\Events\InvoiceIssued;
use Modules\Sales\Events\WithholdingIssued;
use Modules\Sri\Listeners\TriggerElectronicCreditNote;
use Modules\Sri\Listeners\TriggerElectronicDebitNote;
use Modules\Sri\Listeners\TriggerElectronicDeliveryGuide;
use Modules\Sri\Listeners\TriggerElectronicInvoice;
use Modules\Sri\Listeners\TriggerElectronicWithholding;

final class EventServiceProvider extends BaseModuleEventServiceProvider
{
    protected $listen = [
        InvoiceIssued::class => [TriggerElectronicInvoice::class],
        CreditNoteIssued::class => [TriggerElectronicCreditNote::class],
        DebitNoteIssued::class => [TriggerElectronicDebitNote::class],
        DeliveryGuideIssued::class => [TriggerElectronicDeliveryGuide::class],
        WithholdingIssued::class => [TriggerElectronicWithholding::class],
    ];
}
