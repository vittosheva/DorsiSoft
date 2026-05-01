<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Forms\Components;

use BackedEnum;
use Closure;
use Filament\Schemas\Components\View;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;
use Modules\Sri\Enums\ElectronicStatusEnum;

final class ElectronicDocumentStatusBadges
{
    public static function make(
        ?Closure $resolveCommercialStateUsing = null,
        ?Closure $resolveElectronicStateUsing = null,
        ?Closure $resolveExtraBadgesUsing = null,
    ): Closure {
        return static function (?Model $record) use ($resolveCommercialStateUsing, $resolveElectronicStateUsing, $resolveExtraBadgesUsing): ?View {
            if (! $record instanceof Model) {
                return null;
            }

            $commercialState = $resolveCommercialStateUsing instanceof Closure
                ? $resolveCommercialStateUsing($record)
                : (method_exists($record, 'getDisplayCommercialStatus') ? $record->getDisplayCommercialStatus() : $record->getAttributeValue('status'));

            $electronicState = $resolveElectronicStateUsing instanceof Closure
                ? $resolveElectronicStateUsing($record)
                : (method_exists($record, 'getElectronicStatus') ? $record->getElectronicStatus() : $record->getAttributeValue('electronic_status'));

            $badges = [
                self::normalizeBadge([
                    'key' => 'commercial',
                    'title' => __('Status'),
                    'label' => self::resolveLabel($commercialState),
                    'color' => self::resolveCommercialColor($commercialState),
                    'order' => 10,
                ]),
                self::normalizeBadge([
                    'key' => 'sri',
                    'title' => __('SRI'),
                    'label' => self::resolveLabel($electronicState),
                    'color' => self::resolveElectronicColor($electronicState),
                    'order' => 20,
                ]),
            ];

            if ($resolveExtraBadgesUsing instanceof Closure) {
                $extraBadges = $resolveExtraBadgesUsing($record);

                if (is_array($extraBadges)) {
                    foreach ($extraBadges as $badge) {
                        if (! is_array($badge)) {
                            continue;
                        }

                        $badges[] = self::normalizeBadge($badge);
                    }
                }
            }

            usort(
                $badges,
                fn (array $left, array $right): int => ($left['order'] <=> $right['order']) ?: ($left['title'] <=> $right['title']),
            );

            return View::make('sales::filament.components.electronic-document-status-badges')
                ->viewData([
                    'badges' => $badges,
                ]);
        };
    }

    /**
     * @param  array{key?: mixed, title?: mixed, label?: mixed, color?: mixed, order?: mixed}  $badge
     * @return array{key: string, title: string, label: string, color: mixed, order: int}
     */
    private static function normalizeBadge(array $badge): array
    {
        $key = is_string($badge['key'] ?? null) && $badge['key'] !== ''
            ? $badge['key']
            : str((string) ($badge['title'] ?? 'extra'))->snake()->toString();

        $title = is_string($badge['title'] ?? null) && $badge['title'] !== ''
            ? $badge['title']
            : __('Status');

        $label = is_string($badge['label'] ?? null) && $badge['label'] !== ''
            ? $badge['label']
            : '—';

        return [
            'key' => $key,
            'title' => $title,
            'label' => $label,
            'color' => $badge['color'] ?? 'gray',
            'order' => self::resolveBadgeOrder($badge, $key),
        ];
    }

    /**
     * @param  array{order?: mixed}  $badge
     */
    private static function resolveBadgeOrder(array $badge, string $key): int
    {
        if (is_int($badge['order'] ?? null)) {
            return $badge['order'];
        }

        return match ($key) {
            'commercial' => 10,
            'sri' => 20,
            'payment' => 30,
            default => 100,
        };
    }

    private static function resolveLabel(mixed $state): string
    {
        if ($state instanceof HasLabel) {
            return $state->getLabel();
        }

        if ($state instanceof BackedEnum) {
            return str((string) $state->value)->replace('_', ' ')->headline()->toString();
        }

        if (is_string($state) && $state !== '') {
            return str($state)->replace('_', ' ')->headline()->toString();
        }

        return __('No processing');
    }

    private static function resolveCommercialColor(mixed $state): string|array|null
    {
        if ($state instanceof HasColor) {
            return $state->getColor();
        }

        $rawState = $state instanceof BackedEnum ? (string) $state->value : (string) $state;

        return match ($rawState) {
            'issued' => 'success',
            'paid', 'fully_applied' => 'info',
            'voided' => 'danger',
            default => 'gray',
        };
    }

    private static function resolveElectronicColor(mixed $state): string|array|null
    {
        if ($state instanceof HasColor) {
            return $state->getColor();
        }

        $rawState = $state instanceof BackedEnum ? (string) $state->value : (string) $state;

        return match ($rawState) {
            ElectronicStatusEnum::XmlGenerated->value,
            ElectronicStatusEnum::Signed->value => 'info',
            ElectronicStatusEnum::Submitted->value => 'warning',
            ElectronicStatusEnum::Authorized->value => 'success',
            ElectronicStatusEnum::Rejected->value,
            ElectronicStatusEnum::Error->value => 'danger',
            default => 'gray',
        };
    }
}
