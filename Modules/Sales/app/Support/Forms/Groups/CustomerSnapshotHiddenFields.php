<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Forms\Groups;

use Filament\Forms\Components\TextInput;
use Modules\Core\Support\CustomerEmailNormalizer;

final class CustomerSnapshotHiddenFields
{
    /**
     * Returns the 7 hidden TextInput fields for the customer snapshot.
     * Spread into the schema array with ...CustomerSnapshotHiddenFields::make().
     *
     * @return array<int, TextInput>
     */
    public static function make(bool $customerEmailAsArray = false): array
    {
        return [
            TextInput::make('customer_name')->hidden()->dehydrated(),
            TextInput::make('customer_trade_name')->hidden()->dehydrated(),
            TextInput::make('customer_identification_type')->hidden()->dehydrated(),
            TextInput::make('customer_identification')->hidden()->dehydrated(),
            TextInput::make('customer_address')->hidden()->dehydrated(),
            TextInput::make('customer_email')
                ->hidden()
                ->dehydrated()
                ->dehydrateStateUsing(fn (mixed $state): array|string|null => $customerEmailAsArray
                    ? CustomerEmailNormalizer::normalizeAsArray($state)
                    : CustomerEmailNormalizer::normalizeAsString($state)),
            TextInput::make('customer_phone')->hidden()->dehydrated(),
        ];
    }
}
