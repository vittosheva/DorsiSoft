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
use Modules\Sales\Livewire\Concerns\SearchesProducts;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\SalesOrderItemTax;
use Modules\Sales\Services\DocumentItemsPersistor;
use Modules\Sales\Services\SalesOrderTotalsCalculator;

final class SalesOrderItems extends Component
{
    use HasMinimumItemsValidation;
    use HasPendingItems;
    use SearchesProducts;

    #[Locked]
    public ?int $orderId = null;

    #[Locked]
    public bool $isReadOnly = false;

    /** @var string Current product/service search query */
    public string $searchQuery = '';

    /** @var array<int, array<string, mixed>> Search results */
    public array $searchResults = [];

    /** @var array<string, bool> Expanded state per item _key */
    public array $expandedItems = [];

    public string $currencySymbol = '$';

    public function mount(?int $orderId = null, ?string $operation = null, int $minimumItemsCount = 0, ?string $minimumItemsValidationMessage = null): void
    {
        $this->orderId = $orderId;
        $this->isReadOnly = $operation === 'view';
        $this->initializeMinimumItemsValidation($minimumItemsCount, $minimumItemsValidationMessage);

        if ($this->orderId) {
            $order = SalesOrder::with(['items.taxes'])->find($this->orderId);

            if ($order) {
                $this->loadFromDatabase($order);
                $this->currencySymbol = MoneyTextInput::symbolForCode($order->currency_code);
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

        $allowedFields = ['quantity', 'unit_price', 'discount_type', 'discount_value', 'description'];

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

    #[On('sales-order-items:persist')]
    public function persistItems(int $documentId): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->validateMinimumItems();

        $this->orderId = $documentId;

        app(DocumentItemsPersistor::class)->sync(
            [
                'item_model' => SalesOrderItem::class,
                'tax_model' => SalesOrderItemTax::class,
                'document_fk' => 'order_id',
                'item_tax_fk' => 'order_item_id',
            ],
            $documentId,
            $this->pendingItems,
        );

        $this->recalculate();

        $order = SalesOrder::with(['items.taxes'])->find($documentId);

        if ($order) {
            $this->loadFromDatabase($order);
        }
    }

    public function render(): View
    {
        return view('sales::livewire.sales-order-items');
    }

    private function recalculate(): void
    {
        if (! $this->orderId) {
            return;
        }

        $order = SalesOrder::with(['items.taxes'])->findOrFail($this->orderId);
        app(SalesOrderTotalsCalculator::class)->recalculate($order);
    }
}
