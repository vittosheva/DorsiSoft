<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Jobs\ProcessDocumentExtraction;
use Modules\Core\Services\DocumentExtractionStore;
use Modules\Core\Services\DocumentExtractionUploadStore;
use Modules\Core\Services\FileStoragePathService;
use RuntimeException;

final class ExtractDocumentFromFileAction extends Action
{
    private string|Closure|null $documentType = null;

    private ?Closure $insertItemSuggestionsUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Extract from file'))
            ->icon(Heroicon::ArrowUpTray)
            ->color(Color::Indigo)
            ->modalHeading(__('Extract Suggestions From File'))
            ->modalDescription(__('Upload a PDF, JPG, or PNG file, process it in the background, then review and insert the detected suggestions.'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'))
            ->slideOver()
            ->extraModalFooterActions(fn (): array => [
                $this->makeInsertItemSuggestionsAction(),
            ])
            ->schema([
                Section::make(__('Document extraction'))
                    ->description(__('Process commercial documents without saving the current record first.'))
                    ->footerActions([
                        Action::make('processExtraction')
                            ->label(__('Process file'))
                            ->icon(Heroicon::ArrowPath)
                            ->color(Color::Indigo)
                            ->visible(fn (Get $get): bool => filled($get('document_extraction_file_path')) && blank($get('document_extraction_result_id')))
                            ->action(function (Get $get, Set $set): void {
                                $documentType = (string) ($this->evaluate($this->documentType) ?? 'commercial_document');

                                try {
                                    $storedUpload = app(DocumentExtractionUploadStore::class)->persist(
                                        $get('document_extraction_file_path'),
                                    );
                                } catch (RuntimeException $exception) {
                                    Notification::make()
                                        ->title($exception->getMessage())
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $resultId = app(DocumentExtractionStore::class)->createQueuedExtraction(
                                    $storedUpload['original_filename'],
                                    $documentType,
                                    $storedUpload['disk'],
                                    $storedUpload['path'],
                                    Auth::id(),
                                );

                                ProcessDocumentExtraction::dispatch($resultId);

                                $set('document_extraction_file_path', $storedUpload['path']);
                                $set('document_extraction_result_id', $resultId);
                                $set('document_extraction_status_key', 'queued');
                                $set('document_extraction_status', __('Queued'));
                                $set('document_extraction_header_preview', null);
                                $set('document_extraction_items_preview', null);
                                $set('document_extraction_raw_text', null);

                                Notification::make()
                                    ->title(__('Extraction queued. Refresh in a few seconds to load the result.'))
                                    ->info()
                                    ->send();
                            }),

                        Action::make('refreshExtraction')
                            ->label(__('Refresh result'))
                            ->icon(Heroicon::ArrowPath)
                            ->color(Color::Gray)
                            ->visible(fn (Get $get): bool => filled($get('document_extraction_result_id')) && $get('document_extraction_status_key') !== 'completed')
                            ->action(function (Get $get, Set $set): void {
                                $extraction = app(DocumentExtractionStore::class)->find((int) $get('document_extraction_result_id'));

                                if ($extraction === null) {
                                    Notification::make()
                                        ->title(__('The extraction result could not be found.'))
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                $set('document_extraction_status_key', (string) $extraction['status']);
                                $set('document_extraction_status', __(Str::headline((string) $extraction['status'])));
                                $set('document_extraction_header_preview', self::formatHeadersPreview((array) data_get($extraction, 'extracted_data.headers', [])));
                                $set('document_extraction_items_preview', self::formatItemsPreview((array) data_get($extraction, 'extracted_data.items', [])));
                                $set('document_extraction_raw_text', data_get($extraction, 'extracted_data.raw_text'));

                                if ($extraction['status'] === 'completed') {
                                    Notification::make()
                                        ->title(__('Extraction result loaded.'))
                                        ->success()
                                        ->send();

                                    return;
                                }

                                if ($extraction['status'] === 'failed') {
                                    Notification::make()
                                        ->title((string) ($extraction['error_message'] ?: __('The file could not be processed.')))
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                Notification::make()
                                    ->title(__('The file is still being processed.'))
                                    ->info()
                                    ->send();
                            }),
                    ])
                    ->schema([
                        Hidden::make('document_extraction_result_id')
                            ->dehydrated(false),

                        Hidden::make('document_extraction_status_key')
                            ->default('idle')
                            ->dehydrated(false),

                        FileUpload::make('document_extraction_file_path')
                            ->label(__('Source file'))
                            ->helperText(__('Accepted formats: PDF, JPG, PNG.'))
                            ->acceptedFileTypes(FileStoragePathService::getAcceptedTypes(FileTypeEnum::DocumentExtractionUploads))
                            ->disk(FileStoragePathService::getDisk(FileTypeEnum::DocumentExtractionUploads))
                            ->directory(fn (): string => FileStoragePathService::getPath(
                                FileTypeEnum::DocumentExtractionUploads,
                                context: ['record_id' => 'pending'],
                            ))
                            ->afterStateUpdated(function (Set $set): void {
                                $set('document_extraction_result_id', null);
                                $set('document_extraction_status_key', 'idle');
                                $set('document_extraction_status', __('Waiting for file'));
                                $set('document_extraction_header_preview', null);
                                $set('document_extraction_items_preview', null);
                                $set('document_extraction_raw_text', null);
                            })
                            ->maxSize(FileStoragePathService::getMaxSizeKb(FileTypeEnum::DocumentExtractionUploads))
                            ->visibility(FileStoragePathService::getVisibility(FileTypeEnum::DocumentExtractionUploads))
                            ->columnSpanFull(),

                        TextInput::make('document_extraction_status')
                            ->label(__('Status'))
                            ->readOnly()
                            ->dehydrated(false)
                            ->default(__('Waiting for file')),

                        Section::make(__('Header suggestions'))
                            ->schema([
                                Textarea::make('document_extraction_header_preview')
                                    ->hiddenLabel()
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->rows(6)
                                    ->placeholder(__('Detected header suggestions will appear here after processing.')),
                            ])
                            ->hiddenJs(
                                <<<'JS'
                                    !$get('document_extraction_header_preview')
                                JS
                            )
                            ->collapsible()
                            ->collapsed(false)
                            ->columns(1)
                            ->columnSpanFull(),

                        Section::make(__('Item suggestions'))
                            ->schema([
                                Textarea::make('document_extraction_items_preview')
                                    ->hiddenLabel()
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->rows(8)
                                    ->placeholder(__('Detected item suggestions will appear here after processing.')),
                            ])
                            ->hiddenJs(
                                <<<'JS'
                                    !$get('document_extraction_items_preview')
                                JS
                            )
                            ->collapsible()
                            ->collapsed()
                            ->columns(1)
                            ->columnSpanFull(),

                        Section::make(__('Extracted text'))
                            ->schema([
                                Textarea::make('document_extraction_raw_text')
                                    ->hiddenLabel()
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->rows(12)
                                    ->placeholder(__('The extracted OCR text will appear here after processing.')),
                            ])
                            ->hiddenJs(
                                <<<'JS'
                                    !$get('document_extraction_raw_text')
                                JS
                            )
                            ->collapsible()
                            ->collapsed()
                            ->columns(1)
                            ->columnSpanFull(),

                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function documentType(string|Closure $documentType): static
    {
        $this->documentType = $documentType;

        return $this;
    }

    public function insertItemSuggestionsUsing(Closure $callback): static
    {
        $this->insertItemSuggestionsUsing = $callback;

        return $this;
    }

    /**
     * @param  array<string, string>  $headers
     */
    private static function formatHeadersPreview(array $headers): ?string
    {
        $headers = array_filter($headers, fn (string $value): bool => $value !== '');

        if ($headers === []) {
            return null;
        }

        return collect($headers)
            ->map(fn (string $value, string $key): string => Str::headline($key).': '.$value)
            ->implode(PHP_EOL);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private static function formatItemsPreview(array $items): ?string
    {
        if ($items === []) {
            return null;
        }

        return collect($items)
            ->map(function (array $item, int $index): string {
                $quantity = number_format((float) ($item['quantity'] ?? 0), 2, '.', ',');
                $unitPrice = number_format((float) ($item['unit_price'] ?? 0), 2, '.', ',');
                $description = (string) ($item['description'] ?? $item['product_name'] ?? __('Item'));

                return sprintf('%d. %s | %s x %s', $index + 1, $description, $quantity, $unitPrice);
            })
            ->implode(PHP_EOL);
    }

    private function makeInsertItemSuggestionsAction(): Action
    {
        return Action::make('insertItemSuggestions')
            ->label(__('Insert item suggestions'))
            ->icon(Heroicon::CheckCircle)
            ->color('primary')
            ->visible(fn (?array $schemaState): bool => ($schemaState['document_extraction_status_key'] ?? null) === 'completed' && filled($schemaState['document_extraction_items_preview'] ?? null))
            ->action(function (array $data, Component $livewire): void {
                $extraction = $this->resolveCompletedExtraction((int) ($data['document_extraction_result_id'] ?? 0));

                if ($extraction === null) {
                    return;
                }

                $items = (array) data_get($extraction, 'extracted_data.items', []);

                if ($items === []) {
                    Notification::make()
                        ->title(__('No item suggestions were detected for this document.'))
                        ->info()
                        ->send();

                    return;
                }

                if ($this->insertItemSuggestionsUsing === null) {
                    Notification::make()
                        ->title(__('No item insertion callback was configured for this document.'))
                        ->warning()
                        ->send();

                    return;
                }

                $this->evaluate($this->insertItemSuggestionsUsing, [
                    'items' => $items,
                    'livewire' => $livewire,
                    'extraction' => $extraction,
                ], [
                    Component::class => $livewire,
                ]);

                Notification::make()
                    ->title(__('Item suggestions inserted into the document.'))
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveCompletedExtraction(int $resultId): ?array
    {
        $extraction = app(DocumentExtractionStore::class)->find($resultId);

        if ($extraction === null) {
            Notification::make()
                ->title(__('The extraction result could not be found.'))
                ->danger()
                ->send();

            return null;
        }

        if ($extraction['status'] !== 'completed') {
            Notification::make()
                ->title(__('Refresh the extraction result after processing finishes.'))
                ->info()
                ->send();

            return null;
        }

        return $extraction;
    }
}
