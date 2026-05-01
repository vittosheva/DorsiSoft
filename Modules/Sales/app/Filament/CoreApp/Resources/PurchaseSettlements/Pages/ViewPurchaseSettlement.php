<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Sales\Enums\PurchaseSettlementStatusEnum;
use Modules\Sales\Filament\Concerns\InteractsWithPurchaseSettlementHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\PurchaseSettlementResource;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\WithholdingResource;
use Modules\Sales\Models\Withholding;
use Modules\Sales\Models\WithholdingItem;
use Modules\Sales\Services\Tax\WithholdingCalculator;
use Modules\Sales\Services\WithholdingCalculationService;
use Modules\Sales\Services\WithholdingSuggestionService;
use Modules\Sri\Support\Concerns\InteractsWithElectronicAuditPanel;
use Modules\Sri\Support\Concerns\ShowsElectronicError;

final class ViewPurchaseSettlement extends BaseViewRecord
{
    use InteractsWithElectronicAuditPanel;
    use InteractsWithPurchaseSettlementHeaderActions;
    use InteractsWithSalesDocumentHeaderActions;
    use ShowsElectronicError;

    protected static string $resource = PurchaseSettlementResource::class;

    protected function getFooterWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgetData(): array
    {
        return $this->getElectronicAuditWidgetData();
    }

    protected function getHeaderActions(): array
    {
        $generate = Action::make('generate_withholding')
            ->icon(WithholdingResource::getNavigationIcon())
            ->color('success')
            ->visible(fn (): bool => $this->record->status === PurchaseSettlementStatusEnum::Issued && $this->record->withholdings()->doesntExist())
            ->schema([
                Select::make('concept')
                    ->label(__('Purchase type'))
                    ->options([
                        'servicios' => __('Services'),
                        'bienes' => __('Goods'),
                        'profesionales' => __('Professional Services'),
                    ])
                    ->required()
                    ->default('servicios'),
            ])
            ->action(function (array $data): void {
                $settlement = $this->record;
                $concept = $data['concept'];

                $totals = app(WithholdingCalculator::class)->calculate($settlement, $concept);

                // Filter to IR only — IVA 100% is created explicitly below to prevent duplication
                $suggestions = collect(
                    app(WithholdingSuggestionService::class)->suggestItems(
                        subtotal: (float) $totals['subtotal'],
                        ivaAmount: (float) $totals['iva_total'],
                    )
                )->filter(fn (array $s): bool => ($s['tax_type'] ?? '') === 'IR')->values()->all();

                $withholding = null;

                DB::transaction(function () use ($settlement, $totals, $suggestions, &$withholding): void {
                    $withholding = Withholding::create([
                        'company_id' => $settlement->company_id,
                        'issue_date' => now()->toDateString(),
                        'business_partner_id' => $settlement->supplier_id,
                        'supplier_name' => $settlement->supplier_name,
                        'supplier_identification_type' => $settlement->supplier_identification_type,
                        'supplier_identification' => $settlement->supplier_identification,
                        'supplier_address' => $settlement->supplier_address,
                        'source_document_type' => 'purchase_settlement',
                        'source_document_number' => $settlement->code,
                        'source_document_date' => $settlement->issue_date,
                        'source_purchase_settlement_id' => $settlement->getKey(),
                    ]);

                    if ((float) $totals['iva_withheld'] > 0) {
                        WithholdingItem::create([
                            'withholding_id' => $withholding->getKey(),
                            'withholding_rate_id' => null,
                            'tax_type' => 'IVA',
                            'tax_code' => '2',
                            'tax_rate' => 100,
                            'base_amount' => $totals['iva_total'],
                            'withheld_amount' => $totals['iva_withheld'],
                            'source_document_type' => 'purchase_settlement',
                            'source_document_number' => $settlement->code,
                            'source_document_date' => $settlement->issue_date,
                            'source_purchase_settlement_id' => $settlement->getKey(),
                        ]);
                    }

                    foreach ($suggestions as $s) {
                        WithholdingItem::create([
                            'withholding_id' => $withholding->getKey(),
                            'withholding_rate_id' => $s['withholding_rate_id'] ?? null,
                            'tax_type' => $s['tax_type'] ?? 'IR',
                            'tax_code' => $s['tax_code'] ?? null,
                            'tax_rate' => $s['tax_rate'] ?? 0,
                            'base_amount' => $s['base_amount'] ?? 0,
                            'withheld_amount' => $s['withheld_amount'] ?? 0,
                            'source_document_type' => 'purchase_settlement',
                            'source_document_number' => $settlement->code,
                            'source_document_date' => $settlement->issue_date,
                            'source_purchase_settlement_id' => $settlement->getKey(),
                        ]);
                    }

                    app(WithholdingCalculationService::class)->propagateSourceDocument($withholding);

                    Notification::make()
                        ->success()
                        ->title(__('Withholding created from settlement'))
                        ->body(__('A new withholding has been created based on the data of this settlement. You can review and edit the withholding before sending it to the tax authority.'))
                        ->actions([
                            Action::make('view_withholding')
                                ->button()
                                ->url(
                                    WithholdingResource::getUrl('edit', ['record' => $withholding->getKey()]),
                                    shouldOpenInNewTab: true
                                ),
                        ])
                        ->persistent()
                        ->send();
                });
            })
            ->after(function (): void {
                $this->record->refresh();
            })
            ->requiresConfirmation();

        $viewWithholding = Action::make('view_withholding')
            ->icon(Heroicon::DocumentCheck)
            ->color('info')
            ->visible(fn (): bool => $this->record->withholdings()->exists())
            ->url(fn (): string => WithholdingResource::getUrl('edit', [
                'record' => $this->record->withholdings()->value('id'),
            ]));

        return $this->composeSalesDocumentHeaderActions(
            approvalActions: $this->getPurchaseSettlementApprovalActions(),
            primaryActions: $this->getSalesDocumentPrimaryActions(),
            electronicActions: [
                $this->getPurchaseSettlementIssueAction(),
                ...$this->getSalesDocumentElectronicActions(PurchaseSettlementResource::class),
            ],
            managementActions: $this->getSalesDocumentManagementActions(
                duplicateAction: $this->getPurchaseSettlementDuplicateAction(),
                extraActions: [$this->getPurchaseSettlementVoidAction(), $generate, $viewWithholding],
            ),
        );
    }
}
