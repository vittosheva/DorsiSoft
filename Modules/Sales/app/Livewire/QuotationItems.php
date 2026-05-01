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
use Modules\Finance\Models\PriceListItem;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Models\Product;
use Modules\Sales\Livewire\Concerns\HasMinimumItemsValidation;
use Modules\Sales\Livewire\Concerns\HasPendingItems;
use Modules\Sales\Livewire\Concerns\SearchesProducts;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\QuotationItem;
use Modules\Sales\Models\QuotationItemTax;
use Modules\Sales\Services\DocumentItemsPersistor;
use Modules\Sales\Services\QuotationTotalsCalculator;

final class QuotationItems extends Component
{
    use HasMinimumItemsValidation;
    use HasPendingItems;
    use SearchesProducts;

    #[Locked]
    public ?int $quotationId = null;

    #[Locked]
    public bool $isReadOnly = false;

    /** @var string Current product/service search query */
    public string $searchQuery = '';

    /** @var array<int, array<string, mixed>> Search results */
    public array $searchResults = [];

    /** @var array<string, bool> Expanded state per item _key */
    public array $expandedItems = [];

    public string $currencySymbol = '$';

    public function mount(?int $quotationId = null, ?string $operation = null, int $minimumItemsCount = 0, ?string $minimumItemsValidationMessage = null): void
    {
        $this->quotationId = $quotationId;
        $this->isReadOnly = $operation === 'view';
        $this->initializeMinimumItemsValidation($minimumItemsCount, $minimumItemsValidationMessage);

        if ($this->quotationId) {
            $quotation = Quotation::with(['items.taxes'])->find($this->quotationId);

            if ($quotation) {
                $this->loadFromDatabase($quotation);
                $this->currencySymbol = MoneyTextInput::symbolForCode($quotation->currency_code);
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
            'unit_price' => (float) $this->resolveProductPrice($product),
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

    #[On('quotation-items:load-from-extraction')]
    public function loadFromExtraction(array $items): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->pendingItems = [];
        $this->expandedItems = [];
        $this->itemTaxErrors = [];

        foreach ($items as $sortOrder => $item) {
            $description = (string) ($item['description'] ?? $item['product_name'] ?? '');
            $key = Str::uuid()->toString();

            $this->pendingItems[] = [
                '_key' => $key,
                'db_id' => null,
                'product_id' => $item['product_id'] ?? null,
                'product_code' => $item['product_code'] ?? null,
                'product_name' => $item['product_name'] ?? $description,
                'product_unit' => $item['product_unit'] ?? null,
                'sort_order' => $sortOrder + 1,
                'description' => $description,
                'detail_1' => $item['detail_1'] ?? null,
                'detail_2' => $item['detail_2'] ?? null,
                'quantity' => $this->normalizeExtractedDecimal($item['quantity'] ?? 1),
                'unit_price' => $this->normalizeExtractedDecimal($item['unit_price'] ?? 0),
                'discount_type' => $item['discount_type'] ?? null,
                'discount_value' => ($item['discount_value'] ?? null) !== null
                    ? $this->normalizeExtractedDecimal($item['discount_value'])
                    : null,
                'discount_amount' => 0.0,
                'tax_amount' => 0.0,
                'subtotal' => 0.0,
                'total' => $this->normalizeExtractedDecimal($item['line_total'] ?? 0),
                'taxes' => $item['taxes'] ?? [],
            ];

            $this->expandedItems[$key] = false;
            $this->recalculatePendingItemTotals($key);
        }

        $this->dispatchDocumentItemsCountUpdated();
    }

    #[On('quotation-items:persist')]
    public function persistItems(int $documentId): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->validateMinimumItems();

        $this->quotationId = $documentId;

        app(DocumentItemsPersistor::class)->sync(
            [
                'item_model' => QuotationItem::class,
                'tax_model' => QuotationItemTax::class,
                'document_fk' => 'quotation_id',
                'item_tax_fk' => 'quotation_item_id',
            ],
            $documentId,
            $this->pendingItems,
        );

        $this->recalculate();

        $quotation = Quotation::with(['items.taxes'])->find($documentId);

        if ($quotation) {
            $this->loadFromDatabase($quotation);
        }
    }

    public function render(): View
    {
        return view('sales::livewire.quotation-items');
    }

    private function normalizeExtractedDecimal(mixed $value): float
    {
        $normalizedValue = str_replace(',', '.', mb_trim((string) $value));

        if ($normalizedValue === '') {
            return 0.0;
        }

        return (float) number_format((float) $normalizedValue, 2, '.', '');
    }

    private function recalculate(): void
    {
        if (! $this->quotationId) {
            return;
        }

        $quotation = Quotation::with(['items.taxes'])->findOrFail($this->quotationId);
        app(QuotationTotalsCalculator::class)->recalculate($quotation);
    }

    private function resolveProductPrice(Product $product): float|string
    {
        if (! $this->quotationId) {
            return $product->sale_price ?? 0;
        }

        $quotation = Quotation::find($this->quotationId);

        if (! $quotation?->price_list_id) {
            return $product->sale_price ?? 0;
        }

        $priceListItem = PriceListItem::where('price_list_id', $quotation->price_list_id)
            ->where('product_id', $product->id)
            ->orderBy('min_quantity')
            ->first();

        return $priceListItem?->price ?? $product->sale_price ?? 0;
    }
}
