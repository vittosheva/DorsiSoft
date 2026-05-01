<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Collections\Pages;

use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Finance\Filament\CoreApp\Resources\Collections\CollectionResource;
use Modules\Finance\Filament\CoreApp\Resources\Collections\Schemas\CollectionForm;
use Modules\Finance\Models\Collection;
use Modules\Finance\Services\AllocateCollectionToInvoiceService;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\People\Models\BusinessPartner;
use Modules\Sales\Models\Invoice;

final class CreateCollection extends BaseCreateRecord
{
    protected static string $resource = CollectionResource::class;

    /**
     * @var array<int, array{invoice_id?: mixed, amount?: mixed}>
     */
    private array $allocationItems = [];

    public function form(Schema $schema): Schema
    {
        return CollectionForm::configure($schema);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->allocationItems = array_values($data['allocation_items'] ?? []);

        unset($data['allocation_items']);

        if (blank($data['business_partner_id'] ?? null)) {
            throw ValidationException::withMessages([
                'business_partner_id' => 'Debes seleccionar un cliente para registrar el cobro.',
            ]);
        }

        if (blank($data['customer_name'] ?? null)) {
            $data['customer_name'] = BusinessPartner::query()
                ->customers()
                ->whereKey((int) $data['business_partner_id'])
                ->value('legal_name');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Collection $collection */
        $collection = $this->getRecord();

        $this->createAllocations($collection);
    }

    private function createAllocations(Collection $collection): void
    {
        $preparedAllocations = [];
        $allocationTotal = '0.0000';

        $items = collect($this->allocationItems)
            ->filter(fn (array $item): bool => filled($item['invoice_id'] ?? null) && filled($item['amount'] ?? null))
            ->values();

        if ($items->isEmpty()) {
            return;
        }

        $invoiceIds = $items
            ->pluck('invoice_id')
            ->map(fn ($invoiceId): int => (int) $invoiceId)
            ->all();

        if (count($invoiceIds) !== count(array_unique($invoiceIds))) {
            throw ValidationException::withMessages([
                'allocation_items' => 'No puedes repetir una factura en la misma captura de cobro.',
            ]);
        }

        $invoices = Invoice::query()
            ->select(['id', 'total', 'paid_amount'])
            ->whereIn('id', $invoiceIds)
            ->where('business_partner_id', $collection->business_partner_id)
            ->get()
            ->keyBy('id');

        if ($invoices->count() !== count($invoiceIds)) {
            throw ValidationException::withMessages([
                'allocation_items' => 'Una o mas facturas no pertenecen al cliente seleccionado.',
            ]);
        }

        foreach ($items as $item) {
            $invoiceId = (int) $item['invoice_id'];
            $requestedAmount = CollectionAllocationMath::normalize($item['amount'] ?? 0);

            if (bccomp($requestedAmount, '0.0000', CollectionAllocationMath::SCALE) <= 0) {
                throw ValidationException::withMessages([
                    'allocation_items' => 'Todos los montos asignados deben ser mayores que cero.',
                ]);
            }

            /** @var Invoice $invoice */
            $invoice = $invoices->get($invoiceId);
            $pendingInvoiceAmount = CollectionAllocationMath::pending($invoice->total, $invoice->paid_amount);

            if (bccomp($pendingInvoiceAmount, '0.0000', CollectionAllocationMath::SCALE) <= 0) {
                throw ValidationException::withMessages([
                    'allocation_items' => 'La factura seleccionada ya no tiene saldo pendiente.',
                ]);
            }

            if (CollectionAllocationMath::exceedsWithTolerance($requestedAmount, $pendingInvoiceAmount)) {
                throw ValidationException::withMessages([
                    'allocation_items' => 'El monto asignado no puede exceder el saldo pendiente de la factura.',
                ]);
            }

            $finalAllocationAmount = bccomp($requestedAmount, $pendingInvoiceAmount, CollectionAllocationMath::SCALE) > 0
                ? $pendingInvoiceAmount
                : $requestedAmount;

            $preparedAllocations[] = [
                'invoice_id' => $invoiceId,
                'amount' => $finalAllocationAmount,
            ];

            $allocationTotal = bcadd($allocationTotal, $finalAllocationAmount, CollectionAllocationMath::SCALE);
        }

        if (CollectionAllocationMath::exceedsWithTolerance($allocationTotal, $collection->amount)) {
            throw ValidationException::withMessages([
                'allocation_items' => 'La suma de asignaciones no puede exceder el monto del cobro.',
            ]);
        }

        /** @var AllocateCollectionToInvoiceService $allocationService */
        $allocationService = app(AllocateCollectionToInvoiceService::class);

        foreach ($preparedAllocations as $allocation) {
            $allocationService->allocate(
                collection: $collection,
                invoiceId: (int) $allocation['invoice_id'],
                amount: (string) $allocation['amount'],
            );
        }
    }
}
