<?php

declare(strict_types=1);

namespace Modules\People\Enums;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case SUPERVISOR = 'supervisor';
    case ACCOUNTANT = 'accountant';
    case SALES_REP = 'sales_rep';
    case WAREHOUSE_KEEPER = 'warehouse_keeper';
    case BILLING_CLERK = 'billing_clerk';
    case CASHIER = 'cashier';
    case SUPPLIER = 'supplier';
    case AUDITOR = 'auditor';
    case SALES_MANAGER = 'sales_manager';
    case FINANCE_DIRECTOR = 'finance_director';
    case CEO = 'ceo';
    case INVENTORY_MANAGER = 'inventory_manager';
    case PURCHASING_AGENT = 'purchasing_agent';
    case SYSTEM_ADMIN = 'system_admin';
    case COMPLIANCE_AUDITOR = 'compliance_auditor';

    public static function tryFromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    public function displayName(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::SUPERVISOR => 'Supervisor',
            self::ACCOUNTANT => 'Accountant',
            self::SALES_REP => 'Sales Representative',
            self::WAREHOUSE_KEEPER => 'Warehouse Keeper',
            self::BILLING_CLERK => 'Billing Clerk',
            self::CASHIER => 'Cashier',
            self::SUPPLIER => 'Supplier',
            self::AUDITOR => 'Auditor',
            self::SALES_MANAGER => 'Sales Manager',
            self::FINANCE_DIRECTOR => 'Finance Director',
            self::CEO => 'CEO',
            self::INVENTORY_MANAGER => 'Inventory Manager',
            self::PURCHASING_AGENT => 'Purchasing Agent',
            self::SYSTEM_ADMIN => 'System Administrator',
            self::COMPLIANCE_AUDITOR => 'Compliance Auditor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ADMIN => 'Full access to all system features and settings.',
            self::SUPERVISOR => 'Oversees users, roles, and key business operations.',
            self::ACCOUNTANT => 'Manages accounting, finance, and tax operations.',
            self::SALES_REP => 'Handles sales, customer management, and invoicing.',
            self::WAREHOUSE_KEEPER => 'Manages inventory and warehouse operations.',
            self::BILLING_CLERK => 'Responsible for billing, credit, and debit notes.',
            self::CASHIER => 'Processes sales and payments at the point of sale.',
            self::SUPPLIER => 'Manages supplier records and purchase operations.',
            self::AUDITOR => 'Reviews and audits system and business records.',
            self::SALES_MANAGER => 'Leads the sales team and manages sales strategy.',
            self::FINANCE_DIRECTOR => 'Oversees financial planning and management.',
            self::CEO => 'Chief executive with oversight of all company operations.',
            self::INVENTORY_MANAGER => 'Supervises inventory levels and stock movements.',
            self::PURCHASING_AGENT => 'Handles purchasing and procurement activities.',
            self::SYSTEM_ADMIN => 'Manages system configuration and technical settings.',
            self::COMPLIANCE_AUDITOR => 'Ensures regulatory and policy compliance.',
        };
    }
}
