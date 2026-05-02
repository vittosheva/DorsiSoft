<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Entry;
use Filament\Notifications\Livewire\Notifications;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\SlideOverPosition;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter as MalzarieyDateRangeFilter;
use Modules\Core\Support\Actions\CancelAction;
use Tapp\FilamentTimezoneField\Forms\Components\TimezoneSelect;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use YousefAman\ModalRepeater\ModalRepeater;

final class FilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureGlobal();
        $this->configureTables();
        $this->configureForms();
        $this->configureLayouts();
        $this->configureActions();
        $this->configureFilters();
        $this->configureThirdPartyPackages();
    }

    private function configureGlobal(): void
    {
        Page::formActionsAlignment(Alignment::Right);

        Notifications::alignment(Alignment::End);
        Notifications::verticalAlignment(VerticalAlignment::Start);

        Notification::configureUsing(function (Notification $notification): void {
            $notification->duration(6000);
        });
    }

    private function configureTables(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->striped()
                ->poll(null)
                ->searchOnBlur()
                ->searchDebounce(null)
                ->reorderableColumns()
                ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
                ->modifyUngroupedRecordActionsUsing(fn(Action $action) => $action->iconButton())
                ->columnManagerLayout(ColumnManagerLayout::Modal)
                ->columnManagerTriggerAction(fn(Action $action) => $action->slideOver())
                ->filtersFormColumns(12)
                ->filtersLayout(FiltersLayout::AboveContent)
                ->filtersResetActionPosition(FiltersResetActionPosition::Footer)
                ->filtersApplyAction(fn(Action $action) => $action->label(__('To Filter'))->color('primary')->icon(Phosphor::Funnel))
                ->persistFiltersInSession()
                ->persistSearchInSession(false)
                ->persistColumnSearchesInSession(false)
                ->paginationPageOptions([5, 10, 25, 50]);
        });

        Column::configureUsing(function (Column $column): void {
            $column->translateLabel();
        });
    }

    private function configureForms(): void
    {
        Field::configureUsing(function (Field $field): void {
            $field->translateLabel();
        });

        Entry::configureUsing(function (Entry $entry): void {
            $entry->translateLabel();
        });

        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $now = CarbonImmutable::now();
            $datePicker
                ->minDate($now->subYears(5))
                ->maxDate($now->copy()->addYears(5))
                ->prefixIcon(Heroicon::CalendarDays);
        });

        FileUpload::configureUsing(function (FileUpload $fileUpload): void {
            $fileUpload->preventFilePathTampering();
        });

        RichEditor::configureUsing(function (RichEditor $richEditor): void {
            $richEditor
                ->toolbarButtons([
                    ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                    ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                    ['bulletList', 'orderedList'],
                    ['undo', 'redo'],
                ]);
        });

        Select::configureUsing(function (Select $select): void {
            $select->native(false);
        });

        TextInput::configureUsing(function (TextInput $textInput): void {
            $textInput
                ->autocomplete(false)
                ->skipRenderAfterStateUpdated();
        });
    }

    private function configureLayouts(): void
    {
        Section::configureUsing(function (Section $section): void {
            $section->compact(true)->gap(true);
        });

        Tabs::configureUsing(function (Tabs $tabs): void {
            $tabs->contained(false)->dense();
        });
    }

    private function configureActions(): void
    {
        Action::configureUsing(function (Action $action): void {
            $action
                ->translateLabel()
                ->closeModalByClickingAway(false)
                ->bootUsing(static function () use ($action): void {
                    if (! $action->getSchemaComponent() instanceof ModalRepeater) {
                        if (! $action->isConfirmationRequired()) {
                            $action->slideOver()->slideOverPosition(SlideOverPosition::Start);
                        }
                    }
                })
                ->modalFooterActionsAlignment(Alignment::Right);
        });

        CreateAction::configureUsing(function (CreateAction $action): void {
            $action
                ->hiddenLabel(fn(Action $action, Component $livewire) => $this->checkHiddenLabel($action, $livewire))
                ->tooltip(fn(Action $action, Component $livewire) => $this->checkTooltip($action, $livewire, __('Create')))
                ->icon(Heroicon::Plus)
                ->keyBindings([
                    'F6',
                ]);
        });

        EditAction::configureUsing(function (EditAction $action): void {
            $action
                ->hiddenLabel(fn(Action $action, Component $livewire) => $this->checkHiddenLabel($action, $livewire))
                ->tooltip(fn(Action $action, Component $livewire) => $this->checkTooltip($action, $livewire, __('Edit')))
                ->icon(Heroicon::PencilSquare);
        });

        ViewAction::configureUsing(function (ViewAction $action): void {
            $action
                ->hiddenLabel(fn(Action $action, Component $livewire) => $this->checkHiddenLabel($action, $livewire))
                ->tooltip(fn(Action $action, Component $livewire) => $this->checkTooltip($action, $livewire, __('View')))
                ->icon(Heroicon::Eye)
                ->modalWidth(Width::ScreenTwoExtraLarge)
                ->extraModalFooterActions(fn(): array => [
                    EditAction::make()
                        ->icon(Heroicon::PencilSquare),
                ]);
        });

        DeleteAction::configureUsing(function (DeleteAction $action): void {
            $action
                ->hiddenLabel(fn(Action $action, Component $livewire) => $this->checkHiddenLabel($action, $livewire))
                ->tooltip(fn(Action $action, Component $livewire) => $this->checkTooltip($action, $livewire, __('Delete')))
                ->icon(Heroicon::Trash);
        });

        RestoreAction::configureUsing(function (RestoreAction $action): void {
            $action
                ->hiddenLabel(fn(Action $action, Component $livewire) => $this->checkHiddenLabel($action, $livewire))
                ->tooltip(fn(Action $action, Component $livewire) => $this->checkTooltip($action, $livewire, __('Restore')))
                ->icon(Heroicon::ArrowUturnLeft);
        });

        ReplicateAction::configureUsing(function (ReplicateAction $action): void {
            $action
                ->hiddenLabel(fn(Action $action, Component $livewire) => $this->checkHiddenLabel($action, $livewire))
                ->tooltip(fn(Action $action, Component $livewire) => $this->checkTooltip($action, $livewire, __('Replicate')))
                ->icon(Heroicon::DocumentDuplicate)
                ->color('gray');
        });

        DeleteBulkAction::configureUsing(function (DeleteBulkAction $action): void {
            $action->icon(Heroicon::Trash);
        });
    }

    private function configureFilters(): void
    {
        SelectFilter::configureUsing(function (SelectFilter $filter): void {
            $filter
                ->translateLabel()
                ->native(false)
                ->columnSpan(2);
        });
    }

    private function configureThirdPartyPackages(): void
    {
        TimezoneSelect::configureUsing(function (TimezoneSelect $select): void {
            $select->searchable();
        });

        MalzarieyDateRangeFilter::configureUsing(function (MalzarieyDateRangeFilter $filter): void {
            $filter->placeholder(__('Select date range'))->columnSpan(2);
        });
    }

    private function checkHiddenLabel(Action $action, Component $livewire): bool
    {
        if ($action->isIconButton() || $livewire instanceof EditRecord || $livewire instanceof ViewRecord) {
            return true;
        }

        return false;
    }

    private function checkTooltip(Action $action, Component $livewire, string $label): ?string
    {
        if ($action->isIconButton() || $livewire instanceof EditRecord || $livewire instanceof ViewRecord) {
            return $label;
        }

        return null;
    }
}
