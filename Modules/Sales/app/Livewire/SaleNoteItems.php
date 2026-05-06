<?php

declare(strict_types=1);

namespace Modules\Sales\Livewire;

use Illuminate\Database\Eloquent\Collection;
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
use Modules\Sales\Livewire\Concerns\HasPriceListSupport;
use Modules\Sales\Livewire\Concerns\SearchesProducts;
use Modules\Sales\Models\SaleNote;
use Modules\Sales\Models\SaleNoteItem;
use Modules\Sales\Models\SaleNoteItemTax;
use Modules\Sales\Services\DocumentItemsPersistor;
use Modules\Sales\Services\SaleNoteTotalsCalculator;

final class SaleNoteItems extends Component
{
    use HasMinimumItemsValidation;
    use HasPendingItems;
    use HasPriceListSupport;
    use SearchesProducts;

    #[Locked]
    public ?int $saleNoteId = null;

    #[Locked]
    public bool $isReadOnly = false;

    public string $searchQuery = '';

    public array $searchResults = [];

    public array $expandedItems = [];

    public string $currencySymbol = '$';

    public function mount(?int $saleNoteId = null, ?string $operation = null, int $minimumItemsCount = 0, ?string $minimumItemsValidationMessage = null): void
    {
        $this->saleNoteId = $saleNoteId;
        $this->isReadOnly = $operation === 'view';
        $this->initializeMinimumItemsValidation($minimumItemsCount, $minimumItemsValidationMessage);

        if ($this->saleNoteId) {
            $saleNote = SaleNote::with(['items.taxes'])->find($this->saleNoteId);

            if ($saleNote) {
                $this->priceListId = $saleNote->price_list_id;
                $this->warehouseId = $saleNote->warehouse_id;
                $this->loadFromDatabase($saleNote);
                $this->currencySymbol = MoneyTextInput::symbolForCode($saleNote->currency_code);
            }
        }

        $this->dispatchDocumentItemsCountUpdated();
    }

    #[Computed]
    public function taxes(): Collection
    {
        return Tax::query()
            ->select(['id', 'name', 'type', 'rate', 'sri_code', 'sri_percentage_code', 'calculation_type', 'is_active'])
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
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
            'unit_price' => (float) $this->resolveProductPrice($product),
        ], $taxes);

        $this->searchQuery = '';
        $this->searchResults = [];
        $this->hasSearchedProducts = false;
    }

    #[On('document-items:clear')]
    public function clearPendingItems(): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->searchQuery = '';
        $this->searchResults = [];
        $this->hasSearchedProducts = false;

        if ($this->saleNoteId) {
            $saleNote = SaleNote::with(['items.taxes'])->find($this->saleNoteId);

            if ($saleNote) {
                $this->loadFromDatabase($saleNote);

                return;
            }
        }

        $this->pendingItems = [];
        $this->expandedItems = [];
        $this->itemTaxErrors = [];

        $this->dispatchDocumentItemsCountUpdated();
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

    #[On('sale-note-items:persist')]
    public function persistItems(int $documentId): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->validateMinimumItems();

        $this->saleNoteId = $documentId;

        app(DocumentItemsPersistor::class)->sync(
            [
                'item_model' => SaleNoteItem::class,
                'tax_model' => SaleNoteItemTax::class,
                'document_fk' => 'sale_note_id',
                'item_tax_fk' => 'sale_note_item_id',
            ],
            $documentId,
            $this->pendingItems,
        );

        $this->recalculate();

        $saleNote = SaleNote::with(['items.taxes'])->find($documentId);

        if ($saleNote) {
            $this->loadFromDatabase($saleNote);
        }
    }

    public function render(): View
    {
        return view('sales::livewire.sale-note-items');
    }

    private function recalculate(): void
    {
        if (! $this->saleNoteId) {
            return;
        }

        $saleNote = SaleNote::with(['items.taxes'])->findOrFail($this->saleNoteId);
        app(SaleNoteTotalsCalculator::class)->recalculate($saleNote);
    }
}
