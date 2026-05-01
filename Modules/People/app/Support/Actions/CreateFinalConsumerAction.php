<?php

declare(strict_types=1);

namespace Modules\People\Support\Actions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Modules\People\Services\FinalConsumerRegistry;

final class CreateFinalConsumerAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('gray')
            ->requiresConfirmation()
            ->visible(function (): bool {
                $tenant = Filament::getTenant();

                if (! $tenant) {
                    return false;
                }

                return ! app(FinalConsumerRegistry::class)->exists($tenant->getKey());
            })
            ->action(function (): void {
                $companyId = Filament::getTenant()?->getKey();

                if (! $companyId) {
                    return;
                }

                $registry = app(FinalConsumerRegistry::class);

                Cache::lock("creating_final_consumer.{$companyId}", 10)->block(5, function () use ($companyId, $registry): void {
                    $registry->ensureExists($companyId);
                });

                $registry->forgetCache($companyId);

                Notification::make()
                    ->title(__('Final Consumer created successfully.'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'create_final_consumer';
    }
}
