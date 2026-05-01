<?php

declare(strict_types=1);

namespace Modules\Sales\Livewire;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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
use Modules\Sales\Livewire\Concerns\SearchesProducts;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Models\PurchaseSettlementItem;
use Modules\Sales\Services\PurchaseSettlementTotalsCalculator;

final class PurchaseSettlementItems extends Component
{
    use HasMinimumItemsValidation;
    use SearchesProducts;

    #[Locked]
    public ?int $purchaseSettlementId = null;

    #[Locked]
    public bool $isReadOnly = false;

    public string $searchQuery = '';

    /** @var array<int, array<string, mixed>> */
    public array $searchResults = [];

    /** @var array<string, bool> */
    public array $expandedItems = [];

    public string $currencySymbol = '$';

    /** @var array<int, array<string, mixed>> */
    public array $pendingItems = [];

    public function hasMinimumItems(): bool
    {
        return count($this->pendingItems) >= $this->minimumItemsCount;
    }

    public function mount(
        ?int $purchaseSettlementId = null,
        ?string $operation = null,
        int $minimumItemsCount = 0,
        ?string $minimumItemsValidationMessage = null,
    ): void {
        $this->purchaseSettlementId = $purchaseSettlementId;
        $this->isReadOnly = $operation === 'view';
        $this->initializeMinimumItemsValidation($minimumItemsCount, $minimumItemsValidationMessage);

        if ($this->purchaseSettlementId) {
            $settlement = PurchaseSettlement::with('items')->find($this->purchaseSettlementId);

            if ($settlement) {
                $this->loadFromDatabase($settlement);
                $this->currencySymbol = MoneyTextInput::symbolForCode($settlement->currency_code);
            }
        }

        $this->dispatchDocumentItemsCountUpdated();
    }

    #[Computed]
    public function ivaTaxes(): Collection
    {
        return Tax::query()
            ->select(['id', 'name', 'type', 'rate', 'sri_code', 'sri_percentage_code'])
            ->active()
            ->where('type', 'IVA')
            ->orderBy('rate')
            ->get();
    }

    public function addProduct(int $productId): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $product = Product::with(['unit:id,symbol'])->findOrFail($productId);

        $defaultTax = $this->ivaTaxes->first();
        $taxRate = $defaultTax ? (float) $defaultTax->rate : 0.0;
        $taxId = $defaultTax?->id;

        $key = Str::uuid()->toString();
        $quantity = 1.0;
        $unitPrice = (float) ($product->sale_price ?? 0);
        $subtotal = round($quantity * $unitPrice, 4);
        $taxAmount = round($subtotal * ($taxRate / 100), 4);

        $this->pendingItems[] = [
            '_key' => $key,
            'db_id' => null,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'product_unit' => $product->unit?->symbol,
            'sort_order' => count($this->pendingItems) + 1,
            'description' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => 0.0,
            'subtotal' => $subtotal,
            'tax_id' => $taxId,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => round($subtotal + $taxAmount, 4),
        ];

        $this->expandedItems[$key] = false;
        $this->searchQuery = '';
        $this->searchResults = [];
        $this->hasSearchedProducts = false;
        $this->dispatchDocumentItemsCountUpdated();
    }

    public function updateItemField(string $key, string $field, mixed $value): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $allowed = ['quantity', 'unit_price', 'discount_amount', 'description', 'tax_id'];

        if (! in_array($field, $allowed, true)) {
            return;
        }

        foreach ($this->pendingItems as &$item) {
            if ($item['_key'] !== $key) {
                continue;
            }

            $item[$field] = $value;

            if ($field === 'tax_id') {
                $tax = $this->ivaTaxes->find((int) $value);
                $item['tax_rate'] = $tax ? (float) $tax->rate : 0.0;
            }

            $this->recalculateItemTotals($item);
            break;
        }

        unset($item);
        $this->dispatchDocumentItemsCountUpdated();
    }

    public function removeItem(string $key): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->pendingItems = array_values(
            array_filter($this->pendingItems, fn ($i) => $i['_key'] !== $key),
        );

        unset($this->expandedItems[$key]);
        $this->dispatchDocumentItemsCountUpdated();
    }

    public function duplicateItem(string $key): void
    {
        if ($this->isReadOnly) {
            return;
        }

        foreach ($this->pendingItems as $item) {
            if ($item['_key'] !== $key) {
                continue;
            }

            $newKey = Str::uuid()->toString();
            $newItem = array_merge($item, [
                '_key' => $newKey,
                'db_id' => null,
                'sort_order' => count($this->pendingItems) + 1,
            ]);

            $this->pendingItems[] = $newItem;
            $this->expandedItems[$newKey] = false;
            break;
        }

        $this->dispatchDocumentItemsCountUpdated();
    }

    #[On('purchase-settlement-items:persist')]
    public function persistItems(int $documentId): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->validateMinimumItems();
        $this->purchaseSettlementId = $documentId;

        DB::transaction(function () use ($documentId): void {
            $keepIds = array_values(array_filter(array_column($this->pendingItems, 'db_id')));

            $query = PurchaseSettlementItem::where('purchase_settlement_id', $documentId);

            if ($keepIds !== []) {
                $query->whereNotIn('id', $keepIds);
            }

            $query->delete();

            foreach ($this->pendingItems as $pending) {
                $data = [
                    'purchase_settlement_id' => $documentId,
                    'product_id' => $pending['product_id'],
                    'product_code' => $pending['product_code'],
                    'product_name' => $pending['product_name'],
                    'product_unit' => $pending['product_unit'] ?? null,
                    'sort_order' => $pending['sort_order'],
                    'description' => $pending['description'] ?? null,
                    'quantity' => $pending['quantity'],
                    'unit_price' => $pending['unit_price'],
                    'discount_amount' => $pending['discount_amount'] ?? 0,
                    'subtotal' => $pending['subtotal'],
                    'tax_amount' => $pending['tax_amount'],
                    'total' => $pending['total'],
                ];

                if ($pending['db_id'] !== null) {
                    PurchaseSettlementItem::where('id', $pending['db_id'])->update($data);
                } else {
                    PurchaseSettlementItem::create($data);
                }
            }
        });

        $settlement = PurchaseSettlement::with('items')->findOrFail($documentId);
        app(PurchaseSettlementTotalsCalculator::class)->recalculate($settlement);

        $this->loadFromDatabase(PurchaseSettlement::with('items')->find($documentId) ?? $settlement);
    }

    #[Computed]
    public function pendingTotals(): array
    {
        $subtotal = '0.0000';
        $taxAmount = '0.0000';

        foreach ($this->pendingItems as $item) {
            $subtotal = bcadd($subtotal, (string) ($item['subtotal'] ?? 0), 4);
            $taxAmount = bcadd($taxAmount, (string) ($item['tax_amount'] ?? 0), 4);
        }

        return [
            'subtotal' => (float) $subtotal,
            'tax_amount' => (float) $taxAmount,
            'total' => (float) bcadd($subtotal, $taxAmount, 4),
        ];
    }

    public function render(): View
    {
        return view('sales::livewire.purchase-settlement-items');
    }

    private function loadFromDatabase(PurchaseSettlement $settlement): void
    {
        $this->pendingItems = [];
        $this->expandedItems = [];

        foreach ($settlement->items as $item) {
            $key = (string) $item->getKey();

            $this->pendingItems[] = [
                '_key' => $key,
                'db_id' => $item->getKey(),
                'product_id' => $item->product_id,
                'product_code' => $item->product_code,
                'product_name' => $item->product_name,
                'product_unit' => $item->product_unit,
                'sort_order' => $item->sort_order,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount,
                'subtotal' => (float) $item->subtotal,
                'tax_id' => null,
                'tax_rate' => 0.0,
                'tax_amount' => (float) $item->tax_amount,
                'total' => (float) $item->total,
            ];

            $this->expandedItems[$key] = false;
        }
    }

    private function recalculateItemTotals(array &$item): void
    {
        $gross = bcmul((string) $item['quantity'], (string) $item['unit_price'], 8);
        $discount = min((float) ($item['discount_amount'] ?? 0), (float) $gross);
        $subtotal = (float) bcsub($gross, (string) $discount, 4);
        $taxAmount = round($subtotal * (((float) ($item['tax_rate'] ?? 0)) / 100), 4);

        $item['subtotal'] = $subtotal;
        $item['tax_amount'] = $taxAmount;
        $item['total'] = round($subtotal + $taxAmount, 4);
    }
}
