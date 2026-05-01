<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Sales\Events\WithholdingPostedToAccounting;
use Modules\Sales\Models\Withholding;

/**
 * Phase 2: Generate journal entries when accounting tables exist.
 *
 * Expected entry structure once chart of accounts is available:
 *   Debit:  Accounts Payable (supplier) → total withheld
 *   Credit: IVA Withholding Payable SRI → sum of IVA items
 *   Credit: Renta Withholding Payable SRI → sum of IR items
 *
 * Each withholding type should map to a distinct GL account
 * configured per company via AccountingConfiguration (pending).
 */
final class WithholdingAccountingService
{
    public function postWithholding(Withholding $withholding): void
    {
        event(new WithholdingPostedToAccounting($withholding));
    }
}
