<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use Throwable;

final class ValidateSriAction extends Action
{
    /**
     * @var (Closure(Get): bool)|null
     */
    private ?Closure $isValueValidUsing = null;

    /**
     * @var (Closure(Get, Set): bool)|null
     */
    private ?Closure $performValidationUsing = null;

    /**
     * @var (Closure(Get): bool)|null
     */
    private ?Closure $visibleWhenReadyUsing = null;

    private string $loadingStatePath = 'sri_loading';

    private string $validatedStatePath = 'sri_validated';

    private string $invalidTitle = 'Enter a valid value to validate.';

    private string $failureTitle = 'Unable to validate with the SRI.';

    private string $successTitle = 'Validated successfully in the SRI.';

    private string $noInformationTitle = 'No information found in the SRI.';

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->icon(Heroicon::MagnifyingGlass)
            ->tooltip(__('Validate with SRI manually'))
            ->color(Color::Blue)
            ->size(Size::Small)
            ->action(function (Get $get, Set $set): void {
                if ($this->isValueValidUsing === null || $this->performValidationUsing === null) {
                    throw new RuntimeException(__('ValidateSriAction requires isValueValidUsing() and performValidationUsing().'));
                }

                if (! ($this->isValueValidUsing)($get)) {
                    Notification::make()
                        ->title(__($this->invalidTitle))
                        ->warning()
                        ->send();

                    $set($this->validatedStatePath, false);

                    return;
                }

                $set($this->loadingStatePath, true);
                $validated = false;

                try {
                    $validated = ($this->performValidationUsing)($get, $set);
                } catch (Throwable) {
                    $validated = false;

                    Notification::make()
                        ->title(__($this->failureTitle))
                        ->danger()
                        ->send();
                } finally {
                    $set($this->validatedStatePath, $validated);
                    $set($this->loadingStatePath, false);
                }

                Notification::make()
                    ->title(__($validated ? $this->successTitle : $this->noInformationTitle))
                    ->{$validated ? 'success' : 'warning'}()
                    ->send();
            })
            ->visible(function (Get $get): bool {
                if ((bool) $get($this->loadingStatePath)) {
                    return false;
                }

                if ($this->visibleWhenReadyUsing !== null) {
                    return ($this->visibleWhenReadyUsing)($get);
                }

                return $this->isValueValidUsing !== null
                    ? ($this->isValueValidUsing)($get)
                    : true;
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'validateSri';
    }

    /**
     * @param  Closure(Get): bool  $callback
     */
    public function isValueValidUsing(Closure $callback): static
    {
        $this->isValueValidUsing = $callback;

        return $this;
    }

    /**
     * @param  Closure(Get, Set): bool  $callback
     */
    public function performValidationUsing(Closure $callback): static
    {
        $this->performValidationUsing = $callback;

        return $this;
    }

    /**
     * @param  Closure(Get): bool  $callback
     */
    public function visibleWhenReadyUsing(Closure $callback): static
    {
        $this->visibleWhenReadyUsing = $callback;

        return $this;
    }

    public function loadingStatePath(string $loadingStatePath): static
    {
        $this->loadingStatePath = $loadingStatePath;

        return $this;
    }

    public function validatedStatePath(string $validatedStatePath): static
    {
        $this->validatedStatePath = $validatedStatePath;

        return $this;
    }

    public function invalidTitle(string $invalidTitle): static
    {
        $this->invalidTitle = $invalidTitle;

        return $this;
    }

    public function failureTitle(string $failureTitle): static
    {
        $this->failureTitle = $failureTitle;

        return $this;
    }

    public function successTitle(string $successTitle): static
    {
        $this->successTitle = $successTitle;

        return $this;
    }

    public function noInformationTitle(string $noInformationTitle): static
    {
        $this->noInformationTitle = $noInformationTitle;

        return $this;
    }
}
