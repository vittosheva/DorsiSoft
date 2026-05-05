<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicStatusEnum;

final class ShowElectronicAuditAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(function (?Model $record): string {
                if ($record instanceof HasElectronicBilling && in_array($record->getElectronicStatus(), [ElectronicStatusEnum::Error, ElectronicStatusEnum::Rejected], true)) {
                    return __('Review SRI incident');
                }

                return __('SRI Audit Panel');
            })
            ->tooltip(function (Action $action, ?Model $record): ?string {
                if (! $action->isIconButton()) {
                    return null;
                }

                if ($record instanceof HasElectronicBilling && in_array($record->getElectronicStatus(), [ElectronicStatusEnum::Error, ElectronicStatusEnum::Rejected], true)) {
                    return __('Review SRI incident');
                }

                return __('SRI Audit Panel');
            })
            ->icon(Heroicon::ShieldCheck)
            ->color(function (?Model $record) {
                if ($record instanceof HasElectronicBilling && in_array($record->getElectronicStatus(), [ElectronicStatusEnum::Error, ElectronicStatusEnum::Rejected], true)) {
                    return Color::Indigo;
                }

                return Color::Gray;
            })
            ->visible(fn(?Model $record): bool => $record instanceof HasElectronicBilling && self::hasBeenEmittedToSri($record))
            ->slideOver()
            ->modalHeading(__('SRI Audit Panel'))
            ->modalWidth('7xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'))
            ->modalContent(function (?Model $record): View {
                return view('sri::actions.electronic-audit-modal', [
                    'record' => $record,
                ]);
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'show_electronic_audit';
    }

    private static function hasBeenEmittedToSri(Model $record): bool
    {
        /* $status = $record->getElectronicStatus();

        if (in_array($status, [ElectronicStatusEnum::Submitted, ElectronicStatusEnum::Authorized, ElectronicStatusEnum::Rejected], true)) {
            return true;
        } */

        if ($record->electronic_status instanceof ElectronicStatusEnum) {
            return true;
        }

        return filled($record->getAttribute('electronic_submitted_at'));
    }
}
