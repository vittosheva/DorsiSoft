<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;

final class ListInvoices extends BaseListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // TODO: Implement POS billing page and link it here
            Action::make('pos_billing')
                ->url('#')
                ->icon(Heroicon::OutlinedShoppingCart)
                ->color('success'),
            CreateAction::make(),
        ];
    }
}
