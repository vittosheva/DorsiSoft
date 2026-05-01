<?php

declare(strict_types=1);

namespace Modules\People\Enums;

enum PartnerRoleEnum: string
{
    case CUSTOMER = 'customer';
    case SUPPLIER = 'supplier';
    case CARRIER = 'carrier';
    case LEAD = 'lead';

    public function displayName(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Customer',
            self::SUPPLIER => 'Supplier',
            self::CARRIER => 'Carrier',
            self::LEAD => 'Lead',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CUSTOMER => 'An individual or business that purchases goods or services from the company.',
            self::SUPPLIER => 'An individual or business that provides goods or services to the company.',
            self::CARRIER => 'An individual or business responsible for transporting goods on behalf of the company, often involved in logistics and delivery operations.',
            self::LEAD => 'A potential customer or client who has shown interest in the company\'s products or services but has not yet made a purchase.',
        };
    }
}
