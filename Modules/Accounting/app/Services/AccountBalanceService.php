<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\AccountBalance;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;

final class AccountBalanceService
{
    /**
     * Aplica los movimientos de un asiento aprobado al snapshot de saldos.
     */
    public function applyEntry(JournalEntry $entry): void
    {
        foreach ($entry->lines as $line) {
            $balance = $this->findOrCreateBalance($line->account, $entry->fiscalPeriod);

            $balance->period_debit = bcadd((string) $balance->period_debit, (string) $line->debit_base, 4);
            $balance->period_credit = bcadd((string) $balance->period_credit, (string) $line->credit_base, 4);
            $balance->recalculateClosingBalance();
            $balance->save();

            $this->propagateToParents($line->account, $entry->fiscalPeriod, $line->debit_base, $line->credit_base);
        }
    }

    /**
     * Revierte el impacto de un asiento (para anulaciones).
     */
    public function reverseEntry(JournalEntry $entry): void
    {
        foreach ($entry->lines as $line) {
            $balance = $this->findOrCreateBalance($line->account, $entry->fiscalPeriod);

            $balance->period_debit = bcsub((string) $balance->period_debit, (string) $line->debit_base, 4);
            $balance->period_credit = bcsub((string) $balance->period_credit, (string) $line->credit_base, 4);
            $balance->recalculateClosingBalance();
            $balance->save();

            $this->propagateToParents(
                $line->account,
                $entry->fiscalPeriod,
                bcmul('-1', (string) $line->debit_base, 4),
                bcmul('-1', (string) $line->credit_base, 4),
            );
        }
    }

    /**
     * Devuelve el saldo de cierre de una cuenta en un período.
     */
    public function getBalance(ChartOfAccount $account, FiscalPeriod $period): string
    {
        $balance = AccountBalance::query()
            ->where('account_id', $account->id)
            ->where('fiscal_period_id', $period->id)
            ->first();

        return $balance?->closing_balance ?? '0.0000';
    }

    private function findOrCreateBalance(ChartOfAccount $account, FiscalPeriod $period): AccountBalance
    {
        return AccountBalance::firstOrCreate(
            ['account_id' => $account->id, 'fiscal_period_id' => $period->id],
            [
                'company_id' => $account->company_id,
                'opening_balance' => '0.0000',
                'period_debit' => '0.0000',
                'period_credit' => '0.0000',
                'closing_balance' => '0.0000',
            ]
        );
    }

    private function propagateToParents(
        ChartOfAccount $account,
        FiscalPeriod $period,
        string $debit,
        string $credit
    ): void {
        if ($account->parent_id === null) {
            return;
        }

        $parent = $account->parent;

        $parentBalance = $this->findOrCreateBalance($parent, $period);
        $parentBalance->period_debit = bcadd((string) $parentBalance->period_debit, $debit, 4);
        $parentBalance->period_credit = bcadd((string) $parentBalance->period_credit, $credit, 4);
        $parentBalance->recalculateClosingBalance();
        $parentBalance->save();

        $this->propagateToParents($parent, $period, $debit, $credit);
    }
}
