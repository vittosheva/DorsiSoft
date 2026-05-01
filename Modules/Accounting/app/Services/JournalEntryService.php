<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Enums\JournalEntryStatusEnum;
use Modules\Accounting\Events\JournalEntryVoided;
use Modules\Accounting\Events\LedgerPosted;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\People\Models\User;

final class JournalEntryService
{
    public function __construct(
        private readonly AccountBalanceService $balanceService,
        private readonly JournalReferenceGenerator $referenceGenerator,
    ) {}

    /**
     * @param  array{
     *     fiscal_period_id: int,
     *     description: string,
     *     entry_date: string,
     *     source_type?: string,
     *     source_id?: int,
     * }  $data
     */
    public function createDraft(array $data): JournalEntry
    {
        $entry = new JournalEntry($data);
        $entry->reference = $this->referenceGenerator->next($data['fiscal_period_id']);
        $entry->status = JournalEntryStatusEnum::Draft;
        $entry->total_debit = '0.0000';
        $entry->total_credit = '0.0000';
        $entry->save();

        return $entry;
    }

    public function addLine(
        JournalEntry $entry,
        ChartOfAccount $account,
        string $debit,
        string $credit,
        ?string $description = null,
        string $currencyCode = 'USD',
        string $exchangeRate = '1.000000',
    ): JournalLine {
        if (! $entry->isDraft()) {
            throw new DomainException(__('Cannot add lines to a non-draft journal entry.'));
        }

        if (! $account->canReceiveEntries()) {
            throw new DomainException(
                __('Account [:code] does not accept entries.', ['code' => $account->code])
            );
        }

        $lineNumber = $entry->lines()->max('line_number') + 1;

        $line = $entry->lines()->create([
            'account_id' => $account->id,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'currency_code' => $currencyCode,
            'exchange_rate' => $exchangeRate,
            'debit_base' => bcmul($debit, $exchangeRate, 4),
            'credit_base' => bcmul($credit, $exchangeRate, 4),
            'line_number' => $lineNumber,
        ]);

        $entry->recalculateTotals();
        $entry->saveQuietly();

        return $line;
    }

    public function approve(JournalEntry $entry, User $user): void
    {
        if (! $entry->canBeApproved()) {
            throw new DomainException(
                __('Journal entry cannot be approved: verify it is balanced and the period is open.')
            );
        }

        DB::transaction(function () use ($entry, $user): void {
            $entry->status = JournalEntryStatusEnum::Approved;
            $entry->approved_at = now();
            $entry->approved_by = $user->id;
            $entry->save();

            $this->balanceService->applyEntry($entry);
        });

        event(new LedgerPosted($entry));
    }

    public function void(JournalEntry $entry, User $user, string $reason): void
    {
        if (! $entry->canBeVoided()) {
            throw new DomainException(__('Only approved journal entries can be voided.'));
        }

        DB::transaction(function () use ($entry, $user, $reason): void {
            $reversingEntry = $this->createReversingEntry($entry);

            $entry->status = JournalEntryStatusEnum::Voided;
            $entry->voided_at = now();
            $entry->voided_by = $user->id;
            $entry->void_reason = $reason;
            $entry->reversed_by_entry_id = $reversingEntry->id;
            $entry->save();

            $this->balanceService->reverseEntry($entry);
        });

        event(new JournalEntryVoided($entry));
    }

    private function createReversingEntry(JournalEntry $original): JournalEntry
    {
        $reversing = $this->createDraft([
            'fiscal_period_id' => $original->fiscal_period_id,
            'description' => __('Reversing: :desc', ['desc' => $original->description]),
            'entry_date' => now()->toDateString(),
            'source_type' => $original->source_type,
            'source_id' => $original->source_id,
        ]);

        foreach ($original->lines as $line) {
            $this->addLine(
                entry: $reversing,
                account: $line->account,
                debit: (string) $line->credit,
                credit: (string) $line->debit,
                description: $line->description,
                currencyCode: $line->currency_code,
                exchangeRate: (string) $line->exchange_rate,
            );
        }

        $this->approve($reversing, filament()->auth()->user());

        return $reversing;
    }
}
