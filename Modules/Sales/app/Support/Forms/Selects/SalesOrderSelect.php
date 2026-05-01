<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Forms\Selects;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Modules\Sales\Enums\SalesOrderStatusEnum;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\SalesOrderResource;
use Modules\Sales\Models\SalesOrder;

final class SalesOrderSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label($label ?? __('Linked Sales Order'))
            ->prefixIcon(SalesOrderResource::getNavigationIcon())
            ->searchable()
            ->preload()
            ->nullable()
            ->live()
            ->getOptionLabelUsing(fn ($value): ?string => filled($value)
                ? SalesOrder::query()->whereKey($value)->value('code')
                : null)
            ->options(function (Get $get): array {
                $currentOrderId = $get(static::getDefaultName());

                $query = SalesOrder::query()
                    ->whereIn('status', self::availableStatuses())
                    ->where(function (Builder $query) use ($currentOrderId): void {
                        $query->whereDoesntHave('invoices');

                        if (filled($currentOrderId)) {
                            $query->orWhere($query->getModel()->qualifyColumn($query->getModel()->getKeyName()), $currentOrderId);
                        }
                    })
                    ->when(
                        filled($get('business_partner_id')),
                        fn ($q) => $q->where('business_partner_id', $get('business_partner_id')),
                        fn ($q) => $q->whereRaw('1 = 0')
                    );

                return $this->getOptionsAndState($query)['options'];
            })
            ->disabled(function (Get $get, Select $component): bool {
                $currentOrderId = $get(static::getDefaultName());

                $query = SalesOrder::query()
                    ->whereIn('status', self::availableStatuses())
                    ->where(function (Builder $query) use ($currentOrderId): void {
                        $query->whereDoesntHave('invoices');

                        if (filled($currentOrderId)) {
                            $query->orWhere($query->getModel()->qualifyColumn($query->getModel()->getKeyName()), $currentOrderId);
                        }
                    })
                    ->when(
                        filled($get('business_partner_id')),
                        fn ($q) => $q->where('business_partner_id', $get('business_partner_id')),
                        fn ($q) => $q->whereRaw('1 = 0')
                    );
                $state = $this->getOptionsAndState($query);

                if (! $state['hasOrders']) {
                    $component->placeholder(__('No available orders'));

                    return true;
                }

                return false;
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'sales_order_id';
    }

    /**
     * Obtiene las opciones y el estado de existencia de órdenes para un cliente.
     *
     * @return array{options: array, hasOrders: bool}
     */
    public function getOptionsAndState(Builder $query): array
    {
        $options = $query->orderBy('code')->limit(10)->pluck('code', 'id')->all();
        $hasOrders = ! empty($options);

        return [
            'options' => $options,
            'hasOrders' => $hasOrders,
        ];
    }

    /**
     * @return list<SalesOrderStatusEnum>
     */
    private static function availableStatuses(): array
    {
        return [
            SalesOrderStatusEnum::Pending,
            SalesOrderStatusEnum::Confirmed,
            SalesOrderStatusEnum::PartiallyInvoiced,
        ];
    }
}
