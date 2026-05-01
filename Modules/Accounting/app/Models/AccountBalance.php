<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Company;
use Modules\Core\Support\Models\BaseModel;

final class AccountBalance extends BaseModel
{
    use HasFactory;

    protected $table = 'fin_account_balances';

    protected $fillable = [
        'company_id',
        'account_id',
        'fiscal_period_id',
        'opening_balance',
        'period_debit',
        'period_credit',
        'closing_balance',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4',
            'period_debit' => 'decimal:4',
            'period_credit' => 'decimal:4',
            'closing_balance' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    /**
     * Recalcula el saldo de cierre: saldo inicial ± movimientos del período.
     * La cuenta de naturaleza débito suma débitos y resta créditos.
     */
    public function recalculateClosingBalance(): void
    {
        $account = $this->account;

        if ($account->isDebitNature()) {
            $this->closing_balance = bcadd(
                bcadd((string) $this->opening_balance, (string) $this->period_debit, 4),
                bcmul('-1', (string) $this->period_credit, 4),
                4
            );
        } else {
            $this->closing_balance = bcadd(
                bcadd((string) $this->opening_balance, (string) $this->period_credit, 4),
                bcmul('-1', (string) $this->period_debit, 4),
                4
            );
        }
    }
}
