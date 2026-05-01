<?php

declare(strict_types=1);

namespace Modules\Sales\Livewire;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Models\Product;
use Modules\Sales\Livewire\Concerns\HasMinimumItemsValidation;
use Modules\Sales\Livewire\Concerns\HasPendingItems;
use Modules\Sales\Livewire\Concerns\SearchesProducts;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\DebitNoteItem;
use Modules\Sales\Models\DebitNoteItemTax;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\DebitNoteTotalsCalculator;
use Modules\Sales\Services\DocumentItemsPersistor;

final class DebitNoteItems extends Component
{
    use HasMinimumItemsValidation;
    use HasPendingItems;
    use SearchesProducts;

    #[Locked]
    public ?int $debitNoteId = null;

    #[Locked]
    public bool $isReadOnly = false;

    public string $searchQuery = '';

    public array $searchResults = [];

    public array $expandedItems = [];

    public string $currencySymbol = '$';

    public function mount(?int $debitNoteId = null, ?string $operation = null, int $minimumItemsCount = 0, ?string $minimumItemsValidationMessage = null): void
    {
        $this->debitNoteId = $debitNoteId;
        $this->isReadOnly = $operation === 'view';
        $this->initializeMinimumItemsValidation($minimumItemsCount, $minimumItemsValidationMessage);

        if ($this->debitNoteId) {
            $debitNote = DebitNote::with(['items.taxes'])->find($this->debitNoteId);

            if ($debitNote) {
                $this->loadFromDatabase($debitNote);
                $this->currencySymbol = MoneyTextInput::symbolForCode($debitNote->currency_code);
            }
        }

        $this->dispatchDocumentItemsCountUpdated();
    }

    #[Computed]
    public function taxes(): Collection
    {
        return Tax::query()->select(['id', 'name', 'type', 'rate', 'sri_code', 'sri_percentage_code', 'calculation_type'])->active()->orderBy('type')->orderBy('name')->get();
    }

    public function addProduct(int $productId): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $product = Product::with(['unit:id,symbol', 'tax', 'taxes'])->findOrFail($productId);

        $taxes = $this->makePendingTaxesFromCatalog($product->defaultTaxes());

        $this->addPendingItem([
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'product_unit' => $product->unit?->symbol,
            'description' => $product->name,
            'quantity' => 1.0,
            'unit_price' => (float) ($product->sale_price ?? 0),
        ], $taxes);

        $this->searchQuery = '';
        $this->searchResults = [];
        $this->hasSearchedProducts = false;
    }

    public function updateItemField(string $key, string $field, mixed $value): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $allowedFields = ['quantity', 'unit_price', 'discount_type', 'discount_value', 'description', 'detail_1', 'detail_2'];

        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        $this->updatePendingField($key, $field, $value);
    }

    public function addTaxToItem(string $itemKey, int $taxId): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $tax = Tax::findOrFail($taxId);

        $this->addPendingTax($itemKey, $this->makePendingTaxFromCatalogTax($tax));
    }

    public function removeTaxFromItem(string $itemKey, string $taxKey): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->removePendingTax($itemKey, $taxKey);
    }

    public function removeItem(string $key): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->removePendingItem($key);
    }

    public function duplicateItem(string $key): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->duplicatePendingItem($key);
    }

    #[On('debit-note-items:load-from-invoice')]
    public function loadFromInvoice(int $invoiceId): void
    {
        $invoice = Invoice::with(['items.taxes'])->find($invoiceId);

        if (! $invoice) {
            return;
        }

        $this->pendingItems = [];
        $this->expandedItems = [];

        foreach ($invoice->items as $item) {
            $taxes = $item->taxes->map(fn ($tax) => $this->makePendingTaxFromSnapshot($tax))->all();

            $key = Str::uuid()->toString();

            $this->pendingItems[] = [
                '_key' => $key,
                'db_id' => null,
                'product_id' => $item->product_id,
                'product_code' => $item->product_code,
                'product_name' => $item->product_name,
                'product_unit' => $item->product_unit,
                'sort_order' => $item->sort_order,
                'description' => $item->description,
                'detail_1' => $item->detail_1 ?? null,
                'detail_2' => $item->detail_2 ?? null,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_type' => $item->getRawOriginal('discount_type'),
                'discount_value' => $item->discount_value !== null ? (float) $item->discount_value : null,
                'discount_amount' => (float) $item->discount_amount,
                'tax_amount' => (float) $item->tax_amount,
                'subtotal' => (float) $item->subtotal,
                'total' => (float) $item->total,
                'taxes' => $taxes,
            ];

            $this->expandedItems[$key] = false;
        }

        $this->dispatchDocumentItemsCountUpdated();
    }

    #[On('debit-note-items:persist')]
    public function persistItems(int $documentId): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->validateMinimumItems();

        $this->debitNoteId = $documentId;

        app(DocumentItemsPersistor::class)->sync(
            [
                'item_model' => DebitNoteItem::class,
                'tax_model' => DebitNoteItemTax::class,
                'document_fk' => 'debit_note_id',
                'item_tax_fk' => 'debit_note_item_id',
            ],
            $documentId,
            $this->pendingItems,
        );

        $this->recalculate();

        $debitNote = DebitNote::with(['items.taxes'])->find($documentId);

        if ($debitNote) {
            $this->loadFromDatabase($debitNote);
        }
    }

    public function render(): View
    {
        return view('sales::livewire.credit-note-items');
    }

    private function recalculate(): void
    {
        if (! $this->debitNoteId) {
            return;
        }

        $debitNote = DebitNote::with(['items.taxes'])->findOrFail($this->debitNoteId);
        app(DebitNoteTotalsCalculator::class)->recalculate($debitNote);
    }
}
