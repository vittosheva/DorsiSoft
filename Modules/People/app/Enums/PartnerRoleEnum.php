<?php

declare(strict_types=1);

namespace Modules\People\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PartnerRoleEnum: string implements HasColor, HasDescription, HasLabel
{
    case CUSTOMER = 'customer';
    case SUPPLIER = 'supplier';
    case CARRIER = 'carrier';
    case LEAD = 'lead';

    public function getLabel(): string|Htmlable|null
    {
        return $this->displayName();
    }

    public function getDescription(): string|Htmlable|null
    {
        return $this->description();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CUSTOMER => Color::Green,
            self::SUPPLIER => Color::Pink,
            self::CARRIER => Color::Amber,
            self::LEAD => Color::Blue,
        };
    }

    public function displayName(): string
    {
        return match ($this) {
            self::CUSTOMER => __('Customer'),
            self::SUPPLIER => __('Supplier'),
            self::CARRIER => __('Carrier'),
            self::LEAD => __('Lead'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CUSTOMER => __('An individual or business that purchases goods or services from the company.'),
            self::SUPPLIER => __('An individual or business that provides goods or services to the company.'),
            self::CARRIER => __('An individual or business responsible for transporting goods on behalf of the company, often involved in logistics and delivery operations.'),
            self::LEAD => __('A potential customer or client who has shown interest in the company\'s products or services but has not yet made a purchase.'),
        };
    }
}
