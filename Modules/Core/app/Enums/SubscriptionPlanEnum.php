<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use ToneGabes\BetterOptions\Contracts\HasExtraText;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum SubscriptionPlanEnum: string implements HasDescription, HasExtraText, HasIcon, HasLabel
{
    case Basic = 'basic';
    case Pro = 'pro';
    case Enterprise = 'enterprise';

    public static function default(): self
    {
        return self::Basic;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Basic => __('Basic'),
            self::Pro => __('Pro'),
            self::Enterprise => __('Enterprise'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Basic => __('Ideal for teams starting operations.'),
            self::Pro => __('Ideal for growing companies.'),
            self::Enterprise => __('Advanced scaling for multiple areas.'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Basic => Phosphor::Eye->thin(),
            self::Pro => Phosphor::Gear->thin(),
            self::Enterprise => Phosphor::Plus->thin(),
        };
    }

    public function getExtraText(): string|Htmlable|null
    {
        return match ($this) {
            self::Basic => __('Free'),
            self::Pro => __('$29/month'),
            self::Enterprise => __('$59/month'),
        };
    }
}
