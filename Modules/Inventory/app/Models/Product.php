<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Models\Traits\HasActiveScope;
use Modules\Core\Models\Traits\HasAutoCode;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Services\FileStoragePathService;
use Modules\Core\Support\Models\BaseModel;
use Modules\Finance\Models\PriceListItem;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Enums\AbcClassificationEnum;
use Modules\Inventory\Enums\BarcodeTypeEnum;
use Modules\Inventory\Enums\ProductTypeEnum;
use Modules\Inventory\Services\ProductDeletionValidator;
use Modules\Inventory\Services\ProductQrCodeService;

final class Product extends BaseModel
{
    use HasActiveScope;
    use HasAutoCode;
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'inv_products';

    protected $fillable = [
        'company_id',
        'code',
        'sku',
        'barcode',
        'barcode_type',
        'name',
        'description',
        'category_id',
        'brand_id',
        'unit_id',
        'tax_id',
        'type',
        'is_inventory',
        'is_for_sale',
        'is_for_purchase',
        'standard_cost',
        'current_unit_cost',
        'sale_price',
        'weight',
        'volume',
        'min_stock',
        'max_stock',
        'reorder_point',
        'image_url',
        'qr_code_path',
        'qr_generated_at',
        'is_active',
        'tracks_lots',
        'tracks_serials',
        'abc_classification',
        'annual_value',
        'abc_calculated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductTypeEnum::class,
            'abc_classification' => AbcClassificationEnum::class,
            'barcode_type' => BarcodeTypeEnum::class,
            'is_inventory' => 'boolean',
            'is_for_sale' => 'boolean',
            'is_for_purchase' => 'boolean',
            'is_active' => 'boolean',
            'tracks_lots' => 'boolean',
            'tracks_serials' => 'boolean',
            'standard_cost' => 'decimal:8',
            'current_unit_cost' => 'decimal:8',
            'sale_price' => 'decimal:8',
            'weight' => 'decimal:6',
            'volume' => 'decimal:6',
            'min_stock' => 'decimal:6',
            'max_stock' => 'decimal:6',
            'reorder_point' => 'decimal:6',
            'annual_value' => 'decimal:2',
            'abc_calculated_at' => 'datetime',
            'qr_generated_at' => 'datetime',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'PRD';
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'inv_product_taxes', 'product_id', 'tax_id')
            ->withPivot('tax_type')
            ->withTimestamps();
    }

    /**
     * @return Collection<int, Tax>
     */
    public function defaultTaxes(): Collection
    {
        $taxes = $this->relationLoaded('taxes')
            ? $this->getRelation('taxes')
            : $this->taxes()->get();

        if ($taxes->isNotEmpty()) {
            return $taxes->filter(fn ($tax) => $tax->is_active === true);
        }

        if ($this->relationLoaded('tax') && $this->tax !== null) {
            return $this->tax->is_active === true ? collect([$this->tax]) : collect();
        }

        if ($this->tax_id !== null) {
            $legacyTax = $this->tax()->first();

            if ($legacyTax !== null && $legacyTax->is_active === true) {
                return collect([$legacyTax]);
            }
        }

        return collect();
    }

    public function priceListItems(): HasMany
    {
        return $this->hasMany(PriceListItem::class, 'product_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class, 'product_id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(SerialNumber::class, 'product_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_id');
    }

    public function availableStockIn(Warehouse $warehouse): float
    {
        $balance = $this->balances()
            ->where('warehouse_id', $warehouse->getKey())
            ->whereNull('lot_id')
            ->first();

        return $balance ? (float) $balance->quantity_available : 0.0;
    }

    #[Scope]
    public function forSale(Builder $query): void
    {
        $query->where('is_for_sale', true);
    }

    #[Scope]
    public function forPurchase(Builder $query): void
    {
        $query->where('is_for_purchase', true);
    }

    protected static function booted(): void
    {
        self::saving(function (Product $product): void {
            app(ProductQrCodeService::class)->sync($product);
        });

        self::deleting(function (Product $product): void {
            app(ProductDeletionValidator::class)->validate($product);
        });

        self::deleted(function (Product $product): void {
            app(ProductQrCodeService::class)->forget($product);
            self::deleteImage($product);
        });
    }

    private static function deleteImage(self $product): void
    {
        if (blank($product->image_url)) {
            return;
        }

        $disk = Storage::disk(FileStoragePathService::getDisk(FileTypeEnum::ProductImages));

        if ($disk->exists($product->image_url)) {
            $disk->delete($product->image_url);
        }
    }
}
