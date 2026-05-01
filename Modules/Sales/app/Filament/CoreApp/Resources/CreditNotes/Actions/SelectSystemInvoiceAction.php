<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;

final class SelectSystemInvoiceAction
{
    public static function make(string $name = 'selectSystemInvoice'): Action
    {
        return Action::make($name)
            ->label(__('To select'))
            ->icon(Heroicon::ArrowUturnRight)
            ->modalHeading(__('Select Authorized Invoice'))
            ->modalWidth(Width::ExtraLarge)
            ->modalSubmitActionLabel(__('To select'))
            ->schema([
                Select::make('invoice_id')
                    ->label(__('Search by number, customer, tax ID...'))
                    ->searchable()
                    ->required()
                    ->preload()
                    ->getSearchResultsUsing(
                        fn (?string $search): array => self::searchInvoices($search)
                    )
                    ->getOptionLabelUsing(
                        fn ($value): ?string => Invoice::withoutGlobalScopes()->select(['id', 'code'])->find($value)?->code
                    )
                    ->noSearchResultsMessage(__('No authorized invoices available'))
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, Set $set, Component $livewire): void {
                $invoice = Invoice::query()
                    ->with(['businessPartner:id,legal_name,trade_name,identification_type,identification_number,tax_address,email,phone,mobile'])
                    ->find($data['invoice_id']);

                if (! $invoice) {
                    return;
                }

                $set('invoice_id', $invoice->getKey());

                $partner = $invoice->businessPartner;
                $set('business_partner_id', $invoice->business_partner_id);
                $set('customer_name', $partner?->legal_name);
                $set('customer_trade_name', $partner?->trade_name);
                $set('customer_identification_type', $partner?->identification_type);
                $set('customer_identification', $partner?->identification_number);
                $set('customer_address', $partner?->tax_address);
                $set('customer_email', $partner?->email);
                $set('customer_phone', $partner?->phone ?? $partner?->mobile);

                $livewire->dispatch('credit-note-items:load-from-invoice', invoiceId: $invoice->getKey());
            })
            ->slideOver(false);
    }

    /**
     * @return array<int|string, string>
     */
    private static function searchInvoices(?string $search): array
    {
        $search = mb_trim((string) $search);

        if (mb_strlen($search) < 2) {
            return [];
        }

        return Invoice::query()
            ->with('businessPartner:id,legal_name,identification_number')
            ->select(['id', 'code', 'total', 'issue_date', 'business_partner_id'])
            ->whereIn('status', [InvoiceStatusEnum::Issued, InvoiceStatusEnum::Paid])
            ->where(function ($query) use ($search): void {
                $query->where('code', 'ilike', $search.'%')
                    ->orWhereHas(
                        'businessPartner',
                        fn ($q) => $q
                            ->where('legal_name', 'ilike', '%'.$search.'%')
                            ->orWhere('identification_number', 'ilike', $search.'%')
                    );
            })
            ->orderByDesc('code')
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (Invoice $invoice): array => [
                $invoice->id => "{$invoice->businessPartner?->legal_name} - {$invoice->code} — \${$invoice->total}",
            ])
            ->all();
    }
}
