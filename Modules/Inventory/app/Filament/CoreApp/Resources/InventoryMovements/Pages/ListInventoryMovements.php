<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\InventoryMovements\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Inventory\Data\AdjustDTO;
use Modules\Inventory\Data\MoveInDTO;
use Modules\Inventory\Data\MoveOutDTO;
use Modules\Inventory\Data\TransferDTO;
use Modules\Inventory\Enums\MovementTypeEnum;
use Modules\Inventory\Exceptions\DuplicateSerialException;
use Modules\Inventory\Exceptions\InsufficientStockException;
use Modules\Inventory\Filament\CoreApp\Resources\InventoryMovements\InventoryMovementResource;
use Modules\Inventory\Models\InventoryDocumentType;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Support\Forms\Selects\ProductSelect;

final class ListInventoryMovements extends BaseListRecords
{
    protected static string $resource = InventoryMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createMovement')
                ->label(__('New Movement'))
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->schema(fn () => [
                    Section::make(__('Main information'))
                        ->icon(Heroicon::DocumentText)
                        ->schema([
                            DatePicker::make('movement_date')
                                ->default(today())
                                ->required()
                                ->columnStart(1),

                            TextInput::make('reference_code')
                                ->maxLength(100),

                            Textarea::make('notes')
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ])
                        ->columns(4),
                    Section::make(__('Details'))
                        ->icon(Heroicon::ListBullet)
                        ->schema([
                            Fieldset::make(__('Documents'))
                                ->schema([
                                    Select::make('document_type_id')
                                        ->options(fn () => InventoryDocumentType::query()
                                            ->active()
                                            ->affectsInventory()
                                            ->where('requires_source_document', false)
                                            ->orderBy('name')
                                            ->pluck('name', 'id'))
                                        ->required()
                                        ->live()
                                        ->columnSpanFull(),
                                ]),

                            Fieldset::make(__('Warehouses'))
                                ->schema([
                                    Select::make('warehouse_id')
                                        ->label(__('Source Warehouse'))
                                        ->options(fn () => Warehouse::query()
                                            ->where('company_id', Filament::getTenant()?->getKey())
                                            ->active()
                                            ->pluck('name', 'id'))
                                        ->required()
                                        ->live(),

                                    Select::make('destination_warehouse_id')
                                        ->label(__('Destination Warehouse'))
                                        ->options(
                                            fn (Get $get) => Warehouse::query()
                                                ->where('company_id', Filament::getTenant()?->getKey())
                                                ->active()
                                                ->when(
                                                    filled($get('warehouse_id')),
                                                    fn ($q) => $q->where('id', '!=', $get('warehouse_id')),
                                                )
                                                ->pluck('name', 'id')
                                        )
                                        ->visible(
                                            fn (Get $get): bool => $this->resolveMovementType(
                                                filled($get('document_type_id')) ? (int) $get('document_type_id') : null
                                            )?->isTransfer() ?? false
                                        )
                                        ->required(
                                            fn (Get $get): bool => $this->resolveMovementType(
                                                filled($get('document_type_id')) ? (int) $get('document_type_id') : null
                                            )?->isTransfer() ?? false
                                        )
                                        ->live(),
                                ])
                                ->columnSpan(2),

                            Group::make()
                                ->schema([
                                    ProductSelect::make()
                                        ->required()
                                        ->live()
                                        ->columnSpan(6),

                                    TextInput::make('quantity')
                                        ->numeric()
                                        ->minValue(0.01)
                                        ->required(fn (Get $get): bool => ! $this->isSerialInMovement($get))
                                        ->visible(fn (Get $get): bool => ! $this->isSerialInMovement($get))
                                        ->columnSpan(2),

                                    TextInput::make('unit_cost')
                                        ->numeric()
                                        ->minValue(0)
                                        ->visible(fn (Get $get): bool => $this->requiresUnitCost(
                                            filled($get('document_type_id')) ? (int) $get('document_type_id') : null
                                        ))
                                        ->required(fn (Get $get): bool => $this->requiresUnitCost(
                                            filled($get('document_type_id')) ? (int) $get('document_type_id') : null
                                        ))
                                        ->columnSpan(2),

                                    Select::make('lot_id')
                                        ->options(fn (Get $get) => filled($get('product_id'))
                                            ? Lot::query()
                                                ->where('company_id', Filament::getTenant()?->getKey())
                                                ->where('product_id', $get('product_id'))
                                                ->active()
                                                ->pluck('code', 'id')
                                            : [])
                                        ->visible(fn (Get $get): bool => filled($get('product_id'))
                                            && (bool) Product::where('id', $get('product_id'))->value('tracks_lots'))
                                        ->searchable()
                                        ->columnSpan(4),

                                    Repeater::make('serial_numbers')
                                        ->simple(
                                            TextInput::make('serial_number')
                                                ->required()
                                                ->distinct()
                                                ->maxLength(150),
                                        )
                                        ->addActionLabel(__('Add serial number'))
                                        ->minItems(1)
                                        ->visible(fn (Get $get): bool => $this->isSerialInMovement($get))
                                        ->columnSpanFull(),
                                ])
                                ->columnStart(1)
                                ->columns(12)
                                ->columnSpanFull(),
                        ])
                        ->columns(3),
                ])
                ->action(fn (array $data) => $this->handleCreateMovement($data))
                ->modalSubmitActionLabel(__('Register Movement'))
                ->keyBindings([
                    'F6',
                ]),
        ];
    }

    private function resolveMovementType(?int $documentTypeId): ?MovementTypeEnum
    {
        if (! $documentTypeId) {
            return null;
        }

        return InventoryDocumentType::find($documentTypeId)?->movement_type;
    }

    private function requiresUnitCost(?int $documentTypeId): bool
    {
        $type = $this->resolveMovementType($documentTypeId);

        return $type !== null && ! $type->isOut() && ! $type->isTransfer();
    }

    private function isSerialInMovement(Get $get): bool
    {
        return filled($get('product_id'))
            && (bool) Product::where('id', $get('product_id'))->value('tracks_serials')
            && ($this->resolveMovementType(
                filled($get('document_type_id')) ? (int) $get('document_type_id') : null
            )?->isIn() ?? false);
    }

    private function handleCreateMovement(array $data): void
    {
        $company = Filament::getTenant();
        $service = app(InventoryService::class);
        $type = $this->resolveMovementType((int) $data['document_type_id']);
        $date = Carbon::parse($data['movement_date']);

        try {
            match (true) {
                $type?->isIn() => $service->moveIn(new MoveInDTO(
                    companyId: $company->getKey(),
                    warehouseId: (int) $data['warehouse_id'],
                    productId: (int) $data['product_id'],
                    documentTypeId: (int) $data['document_type_id'],
                    quantity: $this->resolveQuantity($data),
                    unitCost: (float) ($data['unit_cost'] ?? 0),
                    movementDate: $date,
                    lotId: isset($data['lot_id']) ? (int) $data['lot_id'] : null,
                    serialNumbers: $this->resolveSerialNumbers($data),
                    referenceCode: $data['reference_code'] ?? null,
                    notes: $data['notes'] ?? null,
                )),
                $type?->isOut() => $service->moveOut(new MoveOutDTO(
                    companyId: $company->getKey(),
                    warehouseId: (int) $data['warehouse_id'],
                    productId: (int) $data['product_id'],
                    documentTypeId: (int) $data['document_type_id'],
                    quantity: (float) $data['quantity'],
                    movementDate: $date,
                    lotId: isset($data['lot_id']) ? (int) $data['lot_id'] : null,
                    referenceCode: $data['reference_code'] ?? null,
                    notes: $data['notes'] ?? null,
                )),
                $type?->isTransfer() => $service->transfer(new TransferDTO(
                    companyId: $company->getKey(),
                    fromWarehouseId: (int) $data['warehouse_id'],
                    toWarehouseId: (int) $data['destination_warehouse_id'],
                    productId: (int) $data['product_id'],
                    documentTypeId: (int) $data['document_type_id'],
                    quantity: (float) $data['quantity'],
                    movementDate: $date,
                    lotId: isset($data['lot_id']) ? (int) $data['lot_id'] : null,
                    referenceCode: $data['reference_code'] ?? null,
                    notes: $data['notes'] ?? null,
                )),
                $type?->isAdjustment() => $service->adjust(new AdjustDTO(
                    companyId: $company->getKey(),
                    warehouseId: (int) $data['warehouse_id'],
                    productId: (int) $data['product_id'],
                    documentTypeId: (int) $data['document_type_id'],
                    quantityDelta: (float) $data['quantity'],
                    unitCost: (float) $data['unit_cost'],
                    movementDate: $date,
                    lotId: isset($data['lot_id']) ? (int) $data['lot_id'] : null,
                    referenceCode: $data['reference_code'] ?? null,
                    notes: $data['notes'] ?? null,
                )),
                default => null,
            };

            Notification::make()
                ->title(__('Movement registered successfully.'))
                ->success()
                ->send();
        } catch (InsufficientStockException $e) {
            Notification::make()
                ->title(__('Insufficient stock: :available available, :requested requested.', [
                    'available' => number_format($e->available, 2),
                    'requested' => number_format($e->requested, 2),
                ]))
                ->danger()
                ->send();
        } catch (DuplicateSerialException $e) {
            Notification::make()
                ->title(__('Serial number already exists: :serial', ['serial' => $e->serialNumber]))
                ->danger()
                ->send();
        }
    }

    private function resolveQuantity(array $data): float
    {
        if (isset($data['serial_numbers']) && is_array($data['serial_numbers'])) {
            return (float) count($data['serial_numbers']);
        }

        return (float) $data['quantity'];
    }

    private function resolveSerialNumbers(array $data): array
    {
        if (! isset($data['serial_numbers']) || ! is_array($data['serial_numbers'])) {
            return [];
        }

        // ->simple() Repeater returns a flat array of scalar values
        $first = reset($data['serial_numbers']);
        if (is_array($first)) {
            return array_column($data['serial_numbers'], 'serial_number');
        }

        return array_values(array_filter($data['serial_numbers']));
    }
}
