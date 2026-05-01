<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Closure;
use Filament\Actions\ReplicateAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

final class DuplicateRecordAction extends ReplicateAction
{
    /**
     * @var array<int, string>
     */
    private array $exceptAttributes = [];

    /**
     * @var (Closure(Model, Model): void)|null
     */
    private ?Closure $mutateRecordUsing = null;

    /**
     * @var (Closure(Model, Model): void)|null
     */
    private ?Closure $duplicateRelationsUsing = null;

    /**
     * @var (Closure(Model, Model): string)|null
     */
    private ?Closure $redirectUrlUsing = null;

    /**
     * @var (Closure(Model, Model): string)|null
     */
    private ?Closure $successTitleUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->icon(Heroicon::DocumentDuplicate)
            ->color('gray')
            ->action(function (Model $record): void {
                $newRecord = $record->replicate($this->exceptAttributes);

                if ($this->mutateRecordUsing !== null) {
                    ($this->mutateRecordUsing)($newRecord, $record);
                }

                $newRecord->save();

                if ($this->duplicateRelationsUsing !== null) {
                    ($this->duplicateRelationsUsing)($record, $newRecord);
                }

                $title = $this->successTitleUsing !== null
                    ? ($this->successTitleUsing)($newRecord, $record)
                    : __('Record duplicated');

                Notification::make()
                    ->success()
                    ->title($title)
                    ->send();

                if ($this->redirectUrlUsing !== null) {
                    $this->redirect(($this->redirectUrlUsing)($newRecord, $record));
                }
            })
            ->requiresConfirmation();
    }

    public static function getDefaultName(): ?string
    {
        return 'duplicate';
    }

    /**
     * @param  array<int, string>  $exceptAttributes
     */
    public function exceptAttributes(array $exceptAttributes): static
    {
        $this->exceptAttributes = $exceptAttributes;

        return $this;
    }

    /**
     * @param  Closure(Model, Model): void  $callback
     */
    public function mutateRecordUsing(Closure $callback): static
    {
        $this->mutateRecordUsing = $callback;

        return $this;
    }

    /**
     * @param  Closure(Model, Model): void  $callback
     */
    public function duplicateRelationsUsing(Closure $callback): static
    {
        $this->duplicateRelationsUsing = $callback;

        return $this;
    }

    /**
     * @param  Closure(Model, Model): string  $callback
     */
    public function redirectUrlUsing(Closure $callback): static
    {
        $this->redirectUrlUsing = $callback;

        return $this;
    }

    /**
     * @param  Closure(Model, Model): string  $callback
     */
    public function successTitleUsing(Closure $callback): static
    {
        $this->successTitleUsing = $callback;

        return $this;
    }

    /**
     * Copies the document's items() and their taxes() to the new record.
     * Assumes items() and taxes() are HasMany relations with standard FK naming.
     */
    public function withItemsAndTaxes(string $itemForeignKey, string $taxItemForeignKey): static
    {
        return $this->duplicateRelationsUsing(function (Model $original, Model $duplicate) use ($itemForeignKey, $taxItemForeignKey): void {
            foreach ($original->items()->with('taxes')->get() as $item) {
                $newItem = $item->replicate();
                $newItem->{$itemForeignKey} = $duplicate->getKey();
                $newItem->save();

                foreach ($item->taxes as $tax) {
                    $newTax = $tax->replicate();
                    $newTax->{$taxItemForeignKey} = $newItem->getKey();
                    $newTax->save();
                }
            }
        });
    }
}
