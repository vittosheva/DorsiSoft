<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\AccountNatureEnum;
use Modules\Accounting\Enums\AccountTypeEnum;
use Modules\Accounting\Enums\FiscalPeriodStatusEnum;
use Modules\Accounting\Enums\JournalEntryStatusEnum;
use Modules\Accounting\Models\AccountBalance;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Core\Models\Company;
use Modules\People\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->period = FiscalPeriod::factory()->for($this->company)->create([
        'status' => FiscalPeriodStatusEnum::OPEN,
    ]);

    $this->debitAccount = ChartOfAccount::factory()->for($this->company)->create([
        'code' => '1.1.01',
        'name' => 'Cash',
        'type' => AccountTypeEnum::Asset,
        'nature' => AccountNatureEnum::Debit,
        'allows_entries' => true,
        'is_control' => false,
    ]);

    $this->creditAccount = ChartOfAccount::factory()->for($this->company)->create([
        'code' => '4.1.01',
        'name' => 'Revenue',
        'type' => AccountTypeEnum::Income,
        'nature' => AccountNatureEnum::Credit,
        'allows_entries' => true,
        'is_control' => false,
    ]);

    $this->service = app(JournalEntryService::class);
});

describe('JournalEntry', function (): void {
    it('creates a draft journal entry', function (): void {
        $entry = $this->service->createDraft([
            'fiscal_period_id' => $this->period->id,
            'description' => 'Test entry',
            'entry_date' => today()->toDateString(),
        ]);

        expect($entry->isDraft())->toBeTrue()
            ->and($entry->status)->toBe(JournalEntryStatusEnum::Draft)
            ->and($entry->reference)->toStartWith('JE-');
    });

    it('adds debit and credit lines', function (): void {
        $entry = $this->service->createDraft([
            'fiscal_period_id' => $this->period->id,
            'description' => 'Balanced entry',
            'entry_date' => today()->toDateString(),
        ]);

        $this->service->addLine($entry, $this->debitAccount, '1000.00', '0');
        $this->service->addLine($entry, $this->creditAccount, '0', '1000.00');

        $entry->refresh();

        expect($entry->isBalanced())->toBeTrue()
            ->and($entry->total_debit)->toBe('1000.0000')
            ->and($entry->total_credit)->toBe('1000.0000')
            ->and($entry->lines()->count())->toBe(2);
    });

    it('approves a balanced draft entry and updates account balances', function (): void {
        $entry = $this->service->createDraft([
            'fiscal_period_id' => $this->period->id,
            'description' => 'To approve',
            'entry_date' => today()->toDateString(),
        ]);

        $this->service->addLine($entry, $this->debitAccount, '500.00', '0');
        $this->service->addLine($entry, $this->creditAccount, '0', '500.00');

        $this->service->approve($entry, $this->user);

        $entry->refresh();

        expect($entry->isApproved())->toBeTrue()
            ->and($entry->approved_by)->toBe($this->user->id);

        $debitBalance = AccountBalance::query()
            ->where('account_id', $this->debitAccount->id)
            ->where('fiscal_period_id', $this->period->id)
            ->first();

        expect($debitBalance)->not->toBeNull()
            ->and((float) $debitBalance->period_debit)->toBe(500.0);
    });

    it('prevents approving an unbalanced entry', function (): void {
        $entry = $this->service->createDraft([
            'fiscal_period_id' => $this->period->id,
            'description' => 'Unbalanced',
            'entry_date' => today()->toDateString(),
        ]);

        $this->service->addLine($entry, $this->debitAccount, '1000.00', '0');
        $this->service->addLine($entry, $this->creditAccount, '0', '800.00');

        expect(fn () => $this->service->approve($entry, $this->user))
            ->toThrow(DomainException::class);
    });

    it('voids an approved entry and creates a reversing entry', function (): void {
        $entry = $this->service->createDraft([
            'fiscal_period_id' => $this->period->id,
            'description' => 'To void',
            'entry_date' => today()->toDateString(),
        ]);

        $this->service->addLine($entry, $this->debitAccount, '200.00', '0');
        $this->service->addLine($entry, $this->creditAccount, '0', '200.00');
        $this->service->approve($entry, $this->user);

        $this->actingAs($this->user);
        $this->service->void($entry, $this->user, 'Error en el monto');

        $entry->refresh();

        expect($entry->isVoided())->toBeTrue()
            ->and($entry->void_reason)->toBe('Error en el monto')
            ->and($entry->reversed_by_entry_id)->not->toBeNull();

        $reversingEntry = JournalEntry::find($entry->reversed_by_entry_id);
        expect($reversingEntry->isApproved())->toBeTrue();
    });

    it('cannot add lines to an approved entry', function (): void {
        $entry = $this->service->createDraft([
            'fiscal_period_id' => $this->period->id,
            'description' => 'Approved',
            'entry_date' => today()->toDateString(),
        ]);

        $this->service->addLine($entry, $this->debitAccount, '100.00', '0');
        $this->service->addLine($entry, $this->creditAccount, '0', '100.00');
        $this->service->approve($entry, $this->user);

        expect(fn () => $this->service->addLine($entry, $this->debitAccount, '50.00', '0'))
            ->toThrow(DomainException::class);
    });
});
