<?php

declare(strict_types=1);

namespace Modules\Core\Support\Pdf;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Finance\Models\CollectionAllocationReversal;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\DeliveryGuide;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SaleNote;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\Withholding;

final class PdfDocumentRouteKey
{
    /**
     * @return array<string, class-string<Model&GeneratesPdf>>
     */
    public static function aliases(): array
    {
        return [
            'quotation' => Quotation::class,
            'sales-order' => SalesOrder::class,
            'sale-note' => SaleNote::class,
            'invoice' => Invoice::class,
            'credit-note' => CreditNote::class,
            'debit-note' => DebitNote::class,
            'delivery-guide' => DeliveryGuide::class,
            'collection-reversal' => CollectionAllocationReversal::class,
            'purchase-settlement' => PurchaseSettlement::class,
            'withholding' => Withholding::class,
        ];
    }

    public static function fromModel(Model&GeneratesPdf $document): string
    {
        return self::fromClass($document::class);
    }

    /**
     * @param  class-string<Model&GeneratesPdf>  $modelClass
     */
    public static function fromClass(string $modelClass): string
    {
        $alias = array_search($modelClass, self::aliases(), true);

        if (! is_string($alias)) {
            throw new InvalidArgumentException('Unsupported PDF document model: '.$modelClass);
        }

        return $alias;
    }

    /**
     * @return class-string<Model&GeneratesPdf>|null
     */
    public static function resolve(string $value): ?string
    {
        $aliases = self::aliases();

        if (array_key_exists($value, $aliases)) {
            return $aliases[$value];
        }

        if (class_exists($value) && in_array(GeneratesPdf::class, class_implements($value) ?: [], true)) {
            /** @var class-string<Model&GeneratesPdf> $value */
            return $value;
        }

        return null;
    }
}
