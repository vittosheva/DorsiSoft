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
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceItem;
use Modules\Sales\Models\InvoiceItemTax;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\DocumentItemsPersistor;
use Modules\Sales\Services\InvoiceTotalsCalculator;

final class InvoiceItems extends Component
{
    use HasMinimumItemsValidation;
    use HasPendingItems;
    use SearchesProducts;

    #[Locked]
    public ?int $invoiceId = null;

    #[Locked]
    public bool $isReadOnly = false;

    /** @var string Current product/service search query */
    public string $searchQuery = '';

    /** @var array<int, array<string, mixed>> Search results */
    public array $searchResults = [];

    /** @var array<string, bool> Expanded state per item _key */
    public array $expandedItems = [];

    public string $currencySymbol = '$';

    /**
     * Returns true if the minimum number of items is met.
     */
    public function hasMinimumItems(): bool
    {
        return count($this->pendingItems) >= $this->minimumItemsCount;
    }

    public function mount(?int $invoiceId = null, ?string $operation = null, int $minimumItemsCount = 0, ?string $minimumItemsValidationMessage = null): void
    {
        $this->invoiceId = $invoiceId;
        $this->isReadOnly = $operation === 'view';
        $this->initializeMinimumItemsValidation($minimumItemsCount, $minimumItemsValidationMessage);

        if ($this->invoiceId) {
            $invoice = Invoice::with(['items.taxes'])->find($this->invoiceId);

            if ($invoice) {
                $this->loadFromDatabase($invoice);
                $this->currencySymbol = MoneyTextInput::symbolForCode($invoice->currency_code);
            }
        }

        $this->dispatchDocumentItemsCountUpdated();
    }

    #[Computed]
    public function taxes(): Collection
    {
        return Tax::query()
            ->select(['id', 'name', 'type', 'rate', 'sri_code', 'sri_percentage_code', 'calculation_type'])
            ->active()
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
            'unit_price' => (float) ($product->sale_price ?? 0),
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

        if ($this->invoiceId) {
            $invoice = Invoice::with(['items.taxes'])->find($this->invoiceId);

            if ($invoice) {
                $this->loadFromDatabase($invoice);

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

    #[On('invoice-items:load-from-order')]
    public function loadFromSalesOrder(int $orderId): void
    {
        $order = SalesOrder::with(['items.taxes'])->find($orderId);

        if (! $order) {
            return;
        }

        $this->pendingItems = [];
        $this->expandedItems = [];

        foreach ($order->items as $item) {
            $taxes = $item->taxes->map(fn($tax) => $this->makePendingTaxFromSnapshot($tax))->all();

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

    #[On('invoice-items:persist')]
    public function persistItems(int $documentId): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->validateMinimumItems();

        $this->invoiceId = $documentId;

        app(DocumentItemsPersistor::class)->sync(
            [
                'item_model' => InvoiceItem::class,
                'tax_model' => InvoiceItemTax::class,
                'document_fk' => 'invoice_id',
                'item_tax_fk' => 'invoice_item_id',
            ],
            $documentId,
            $this->pendingItems,
        );

        $this->recalculate();

        $invoice = Invoice::with(['items.taxes'])->find($documentId);

        if ($invoice) {
            $this->loadFromDatabase($invoice);
        }
    }

    public function render(): View
    {
        return view('sales::livewire.invoice-items');
    }

    private function recalculate(): void
    {
        if (! $this->invoiceId) {
            return;
        }

        $invoice = Invoice::with(['items.taxes'])->findOrFail($this->invoiceId);
        app(InvoiceTotalsCalculator::class)->recalculate($invoice);
    }
}
