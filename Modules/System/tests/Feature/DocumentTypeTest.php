<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Company;
use Modules\System\Exceptions\DocumentSeriesNotFoundException;
use Modules\System\Models\DocumentSeries;
use Modules\System\Models\DocumentType;
use Modules\System\Services\DocumentNumberingService;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create();
});

describe('DocumentType', function (): void {
    it('creates a document type scoped to a company', function (): void {
        $type = DocumentType::factory()->for($this->company)->create([
            'code' => 'invoice',
            'name' => 'Factura',
            'sri_code' => '01',
            'affects_accounting' => true,
            'is_electronic' => true,
        ]);

        expect($type->affects_accounting)->toBeTrue()
            ->and($type->is_electronic)->toBeTrue()
            ->and($type->sri_code)->toBe('01');
    });

    it('retrieves behavior flags correctly', function (): void {
        $type = DocumentType::factory()->for($this->company)->create([
            'code' => 'invoice',
            'behavior_flags' => ['max_items' => 100, 'requires_ruc' => true],
        ]);

        expect($type->getBehaviorFlag('max_items'))->toBe(100)
            ->and($type->hasBehaviorFlag('requires_ruc'))->toBeTrue()
            ->and($type->getBehaviorFlag('nonexistent', 'default'))->toBe('default');
    });

    it('enforces unique code per company', function (): void {
        DocumentType::factory()->for($this->company)->create(['code' => 'invoice', 'name' => 'A']);

        expect(fn () => DocumentType::factory()->for($this->company)->create(['code' => 'invoice', 'name' => 'B']))
            ->toThrow(QueryException::class);
    });
});

describe('DocumentSeries', function (): void {
    it('formats sequence with prefix and padding', function (): void {
        $type = DocumentType::factory()->for($this->company)->create(['code' => 'sales_order', 'name' => 'Orden']);
        $series = DocumentSeries::factory()->for($type, 'documentType')->for($this->company)->create([
            'prefix' => 'ORD',
            'padding' => 6,
            'current_sequence' => 0,
        ]);

        $number = $series->increment();

        expect($number)->toBe('ORD-000001')
            ->and($series->current_sequence)->toBe(1);
    });

    it('formats sequence without prefix', function (): void {
        $type = DocumentType::factory()->for($this->company)->create(['code' => 'sales_order', 'name' => 'Orden']);
        $series = DocumentSeries::factory()->for($type, 'documentType')->for($this->company)->create([
            'prefix' => null,
            'padding' => 6,
            'current_sequence' => 5,
        ]);

        expect($series->increment())->toBe('000006');
    });

    it('resets sequence on new year when auto_reset_yearly is enabled', function (): void {
        $type = DocumentType::factory()->for($this->company)->create(['code' => 'invoice', 'name' => 'Factura']);
        $series = DocumentSeries::factory()->for($type, 'documentType')->for($this->company)->create([
            'prefix' => 'FAC',
            'padding' => 6,
            'current_sequence' => 999,
            'auto_reset_yearly' => true,
            'reset_year' => (int) now()->subYear()->format('Y'),
        ]);

        $number = $series->increment();

        expect($number)->toBe('FAC-000001')
            ->and($series->reset_year)->toBe((int) now()->format('Y'));
    });
});

describe('DocumentNumberingService', function (): void {
    it('generates sequential numbers with DB lock', function (): void {
        $type = DocumentType::factory()->for($this->company)->create(['code' => 'invoice', 'name' => 'Factura']);
        DocumentSeries::factory()->for($type, 'documentType')->for($this->company)->create([
            'prefix' => 'INV',
            'padding' => 6,
            'current_sequence' => 0,
            'is_active' => true,
        ]);

        $service = app(DocumentNumberingService::class);

        $first = $service->generate($type);
        $second = $service->generate($type);

        expect($first)->toBe('INV-000001')
            ->and($second)->toBe('INV-000002');
    });

    it('throws DocumentSeriesNotFoundException when no active series exists', function (): void {
        $type = DocumentType::factory()->for($this->company)->create(['code' => 'invoice', 'name' => 'Factura']);

        expect(fn () => app(DocumentNumberingService::class)->generate($type))
            ->toThrow(DocumentSeriesNotFoundException::class);
    });

    it('returns false for hasSeries when no active series exists', function (): void {
        $type = DocumentType::factory()->for($this->company)->create(['code' => 'invoice', 'name' => 'Factura']);

        expect(app(DocumentNumberingService::class)->hasSeries($type))->toBeFalse();
    });
});
