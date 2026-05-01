<?php

declare(strict_types=1);

namespace Modules\People\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\CarrierDetail;
use Modules\People\Models\CarrierVehicle;
use Modules\People\Models\CustomerDetail;
use Modules\People\Models\SupplierDetail;

final class BusinessPartnerSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed demo business partners (customers + suppliers + carriers) for the first registered company.
     *
     * HasAutoCode is bypassed (WithoutModelEvents suppresses the creating event) by
     * generating PER-prefixed codes manually via DB::table()->max().
     * Note: BusinessPartner has no company scope on codes (getCodeScope returns []),
     * so the max-code query intentionally omits company_id.
     *
     * Roles are attached via the core_business_partner_role pivot.
     * CustomerDetail / SupplierDetail / CarrierDetail records are created for each partner as needed.
     *
     * Partners 1–5: role=customer
     * Partner  5:   role=customer + supplier (dual role)
     * Partners 6–8: role=supplier
     * Partners 9–13: role=carrier
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('BusinessPartnerSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        // Lookup role IDs dynamically from enum
        $roleCodes = collect([PartnerRoleEnum::CUSTOMER, PartnerRoleEnum::SUPPLIER, PartnerRoleEnum::CARRIER])
            ->map(fn ($e) => $e->value)
            ->all();
        $roleIds = DB::table('core_partner_roles')
            ->whereIn('code', $roleCodes)
            ->pluck('id', 'code');

        if ($roleIds->count() !== count($roleCodes)) {
            $missing = array_diff($roleCodes, $roleIds->keys()->all());
            $this->command->warn('BusinessPartnerSeeder: Missing partner roles: ['.implode(', ', $missing).']. Run PartnerRoleSeeder first. Skipping.');

            return;
        }

        $definitions = [
            // Customers -------------------------------------------------------
            [
                'identification_type' => 'ruc',
                'identification_number' => '0990001234001',
                'legal_name' => 'Tech Solutions Ecuador S.A.',
                'trade_name' => 'TechSol EC',
                'email' => ['info@techsol.ec'],
                'phone' => '+593 4 2345678',
                'mobile' => '+593 99 1234567',
                'tax_address' => 'Av. 9 de Octubre 123, Guayaquil',
                'roles' => ['customer'],
                'customer_detail' => [
                    'credit_limit' => 10000.00,
                    'credit_balance' => 0.00,
                    'payment_terms_days' => 30,
                    'discount_percentage' => 5.00,
                    'tax_exempt' => false,
                    'rating' => 5,
                ],
            ],
            [
                'identification_type' => 'ruc',
                'identification_number' => '1790023456001',
                'legal_name' => 'Innovatech CIA. LTDA.',
                'trade_name' => 'Innovatech',
                'email' => ['ventas@innovatech.com.ec'],
                'phone' => '+593 2 3456789',
                'mobile' => '+593 98 2345678',
                'tax_address' => 'Calle Bolivia N23-456, Quito',
                'roles' => ['customer'],
                'customer_detail' => [
                    'credit_limit' => 5000.00,
                    'credit_balance' => 0.00,
                    'payment_terms_days' => 15,
                    'discount_percentage' => 3.00,
                    'tax_exempt' => false,
                    'rating' => 4,
                ],
            ],
            [
                'identification_type' => 'cedula',
                'identification_number' => '1704567890',
                'legal_name' => 'Carlos Eduardo Martínez López',
                'trade_name' => null,
                'email' => ['carlos.martinez@gmail.com'],
                'phone' => null,
                'mobile' => '+593 99 3456789',
                'tax_address' => 'Calle Guayaquil N4-789, Quito',
                'roles' => ['customer'],
                'customer_detail' => [
                    'credit_limit' => 2000.00,
                    'credit_balance' => 0.00,
                    'payment_terms_days' => 0,
                    'discount_percentage' => 0.00,
                    'tax_exempt' => false,
                    'rating' => 4,
                ],
            ],
            [
                'identification_type' => 'cedula',
                'identification_number' => '0927654321',
                'legal_name' => 'Ana Sofía Rivera Mendoza',
                'trade_name' => null,
                'email' => ['ana.rivera@hotmail.com'],
                'phone' => null,
                'mobile' => '+593 98 4567890',
                'tax_address' => 'Urb. Los Ceibos Mz. 12 Villa 5, Guayaquil',
                'roles' => ['customer'],
                'customer_detail' => [
                    'credit_limit' => 1500.00,
                    'credit_balance' => 0.00,
                    'payment_terms_days' => 0,
                    'discount_percentage' => 0.00,
                    'tax_exempt' => false,
                    'rating' => 3,
                ],
            ],
            [
                'identification_type' => 'ruc',
                'identification_number' => '0992345678001',
                'legal_name' => 'Empresa Digital S.A.S.',
                'trade_name' => 'DigitalSAS',
                'email' => ['compras@digitalsas.com'],
                'phone' => '+593 4 5678901',
                'mobile' => '+593 99 5678901',
                'tax_address' => 'Km 12 Vía Daule, Guayaquil',
                'roles' => ['customer', 'supplier'],
                'customer_detail' => [
                    'credit_limit' => 8000.00,
                    'credit_balance' => 0.00,
                    'payment_terms_days' => 30,
                    'discount_percentage' => 4.00,
                    'tax_exempt' => false,
                    'rating' => 4,
                ],
                'supplier_detail' => [
                    'payment_terms_days' => 30,
                    'lead_time_days' => 7,
                    'tax_withholding_applicable' => true,
                    'rating' => 4,
                ],
            ],
            // Suppliers -------------------------------------------------------
            [
                'identification_type' => 'ruc',
                'identification_number' => '1790056789001',
                'legal_name' => 'Importaciones Asia Pacífico Cía. Ltda.',
                'trade_name' => 'Asia Pac Imports',
                'email' => ['proveedores@asiapac.ec'],
                'phone' => '+593 2 6789012',
                'mobile' => '+593 99 6789012',
                'tax_address' => 'Av. América N34-56, Quito',
                'roles' => ['supplier'],
                'supplier_detail' => [
                    'payment_terms_days' => 45,
                    'lead_time_days' => 21,
                    'tax_withholding_applicable' => true,
                    'rating' => 5,
                ],
            ],
            [
                'identification_type' => 'ruc',
                'identification_number' => '0990098765001',
                'legal_name' => 'Distribuidora Nacional de Tecnología S.A.',
                'trade_name' => 'DistriTech',
                'email' => ['info@distritech.com.ec'],
                'phone' => '+593 4 7890123',
                'mobile' => '+593 98 7890123',
                'tax_address' => 'Cdla. Kennedy Norte Mz. 3 Villa 8, Guayaquil',
                'roles' => ['supplier'],
                'supplier_detail' => [
                    'payment_terms_days' => 30,
                    'lead_time_days' => 14,
                    'tax_withholding_applicable' => true,
                    'rating' => 4,
                ],
            ],
            [
                'identification_type' => 'ruc',
                'identification_number' => '0990087654001',
                'legal_name' => 'Accesorios y Periféricos del Ecuador S.A.',
                'trade_name' => 'AccePeri',
                'email' => ['ventas@accperi.com.ec'],
                'phone' => '+593 4 8901234',
                'mobile' => null,
                'tax_address' => 'Puerto Santa Ana, Guayaquil',
                'roles' => ['supplier'],
                'supplier_detail' => [
                    'payment_terms_days' => 15,
                    'lead_time_days' => 7,
                    'tax_withholding_applicable' => false,
                    'rating' => 3,
                ],
            ],
            // Carriers --------------------------------------------------------
            [
                'identification_type' => 'ruc',
                'identification_number' => '1791012345001',
                'legal_name' => 'Transporte Logístico Andino S.A.',
                'trade_name' => 'TransAndino',
                'email' => ['operaciones@transandino.ec'],
                'phone' => '+593 2 4012300',
                'mobile' => '+593 99 4012300',
                'tax_address' => 'Panamericana Norte Km 8, Quito',
                'roles' => ['carrier'],
                'carrier_detail' => [
                    'transport_authorization' => 'ANT-TR-2026-001',
                    'authorization_expiry_date' => '2027-12-31',
                    'soat_number' => 'SOAT-TR-001',
                    'soat_expiry_date' => '2027-08-31',
                    'cargo_insurance_number' => 'POL-CARGA-001',
                    'cargo_insurance_expiry_date' => '2027-10-31',
                    'insurance_company' => 'Seguros Pichincha',
                    'insurance_coverage_amount' => 25000.00,
                    'rating' => 5,
                    'notes' => 'Cobertura para rutas Sierra y Costa.',
                ],
                'carrier_vehicles' => [
                    [
                        'driver_name' => 'Jorge Luis Gómez',
                        'driver_identification' => '1712345678',
                        'driver_license' => 'DL-12345678',
                        'driver_license_type' => 'C',
                        'driver_license_expiry_date' => '2028-05-31',
                        'vehicle_plate' => 'ABC-1234',
                        'vehicle_model' => 'Volvo FH16',
                        'vehicle_capacity_tons' => 20.0,
                        'vehicle_year' => 2020,
                    ],
                    [
                        'driver_name' => 'María Fernanda Ruiz',
                        'driver_identification' => '1723456789',
                        'driver_license' => 'DL-87654321',
                        'driver_license_type' => 'C',
                        'driver_license_expiry_date' => '2028-12-31',
                        'vehicle_plate' => 'DEF-5678',
                        'vehicle_model' => 'Scania R450',
                        'vehicle_capacity_tons' => 18.0,
                        'vehicle_year' => 2019,
                    ],
                ],
            ],
            [
                'identification_type' => 'ruc',
                'identification_number' => '0991012345001',
                'legal_name' => 'Carga Express del Pacífico Cía. Ltda.',
                'trade_name' => 'CargaPac',
                'email' => ['trafico@cargapac.ec'],
                'phone' => '+593 4 4012301',
                'mobile' => '+593 98 4012301',
                'tax_address' => 'Vía a Daule Km 14.5, Guayaquil',
                'roles' => ['carrier'],
                'carrier_detail' => [
                    'transport_authorization' => 'ANT-TR-2026-002',
                    'authorization_expiry_date' => '2027-11-30',
                    'soat_number' => 'SOAT-TR-002',
                    'soat_expiry_date' => '2027-07-31',
                    'cargo_insurance_number' => 'POL-CARGA-002',
                    'cargo_insurance_expiry_date' => '2027-09-30',
                    'insurance_company' => 'Seguros Equinoccial',
                    'insurance_coverage_amount' => 20000.00,
                    'rating' => 4,
                    'notes' => 'Especializado en reparto urbano y consolidado.',
                ],
            ],
            [
                'identification_type' => 'ruc',
                'identification_number' => '1791012346001',
                'legal_name' => 'Servicios de Transporte Sierra Centro S.A.S.',
                'trade_name' => 'SierraCentro',
                'email' => ['despacho@sierracentro.ec'],
                'phone' => '+593 3 4012302',
                'mobile' => '+593 99 4012302',
                'tax_address' => 'Av. Indoamérica 2450, Ambato',
                'roles' => ['carrier'],
                'carrier_detail' => [
                    'transport_authorization' => 'ANT-TR-2026-003',
                    'authorization_expiry_date' => '2028-01-31',
                    'soat_number' => 'SOAT-TR-003',
                    'soat_expiry_date' => '2027-06-30',
                    'cargo_insurance_number' => 'POL-CARGA-003',
                    'cargo_insurance_expiry_date' => '2027-12-15',
                    'insurance_company' => 'Latina Seguros',
                    'insurance_coverage_amount' => 18000.00,
                    'rating' => 4,
                    'notes' => 'Cobertura Ambato, Riobamba y Latacunga.',
                ],
            ],
            [
                'identification_type' => 'ruc',
                'identification_number' => '0991012346001',
                'legal_name' => 'Operadora de Fletes Costa Sur S.A.',
                'trade_name' => 'CostaSur Fletes',
                'email' => ['rutas@costasur.ec'],
                'phone' => '+593 5 4012303',
                'mobile' => '+593 98 4012303',
                'tax_address' => 'Av. 25 de Junio y Bolívar, Machala',
                'roles' => ['carrier'],
                'carrier_detail' => [
                    'transport_authorization' => 'ANT-TR-2026-004',
                    'authorization_expiry_date' => '2027-10-15',
                    'soat_number' => 'SOAT-TR-004',
                    'soat_expiry_date' => '2027-05-31',
                    'cargo_insurance_number' => 'POL-CARGA-004',
                    'cargo_insurance_expiry_date' => '2027-11-30',
                    'insurance_company' => 'Aseguradora del Sur',
                    'insurance_coverage_amount' => 22000.00,
                    'rating' => 5,
                    'notes' => 'Rutas frecuentes hacia puertos y zonas bananeras.',
                ],
            ],
            [
                'identification_type' => 'ruc',
                'identification_number' => '1791012347001',
                'legal_name' => 'Logística y Transporte Amazónico Cía. Ltda.',
                'trade_name' => 'LogisAmazon',
                'email' => ['coordinacion@logisamazon.ec'],
                'phone' => '+593 6 4012304',
                'mobile' => '+593 99 4012304',
                'tax_address' => 'Av. Amazonas y Los Shyris, Tena',
                'roles' => ['carrier'],
                'carrier_detail' => [
                    'transport_authorization' => 'ANT-TR-2026-005',
                    'authorization_expiry_date' => '2027-09-30',
                    'soat_number' => 'SOAT-TR-005',
                    'soat_expiry_date' => '2027-04-30',
                    'cargo_insurance_number' => 'POL-CARGA-005',
                    'cargo_insurance_expiry_date' => '2027-10-20',
                    'insurance_company' => 'Confianza Seguros',
                    'insurance_coverage_amount' => 15000.00,
                    'rating' => 3,
                    'notes' => 'Operación para rutas de difícil acceso.',
                ],
            ],
        ];

        // BusinessPartner has no company scope on code (getCodeScope returns []),
        // so we query globally to find the true next sequential code.
        $maxCode = DB::table('core_business_partners')
            ->where('code', 'LIKE', 'PER%')
            ->max('code');

        $seq = $maxCode ? ((int) mb_substr($maxCode, 3)) + 1 : 1;

        $created = 0;

        foreach ($definitions as $data) {
            $exists = DB::table('core_business_partners')
                ->where('company_id', $companyId)
                ->where('identification_type', $data['identification_type'])
                ->where('identification_number', $data['identification_number'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $code = 'PER'.mb_str_pad((string) $seq++, 3, '0', STR_PAD_LEFT);

            $partner = BusinessPartner::create([
                'company_id' => $companyId,
                'code' => $code,
                'identification_type' => $data['identification_type'],
                'identification_number' => $data['identification_number'],
                'legal_name' => $data['legal_name'],
                'trade_name' => $data['trade_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'mobile' => $data['mobile'],
                'tax_address' => $data['tax_address'],
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Attach roles via pivot (withTimestamps)
            foreach ($data['roles'] as $roleCode) {
                // Always resolve role ID by code from enum mapping
                $roleId = $roleIds->get($roleCode);
                if ($roleId) {
                    DB::table('core_business_partner_role')->insert([
                        'business_partner_id' => $partner->getKey(),
                        'partner_role_id' => $roleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (isset($data['customer_detail'])) {
                CustomerDetail::create(array_merge($data['customer_detail'], [
                    'business_partner_id' => $partner->getKey(),
                    'created_by' => 1,
                    'updated_by' => 1,
                ]));
            }

            if (isset($data['supplier_detail'])) {
                SupplierDetail::create(array_merge($data['supplier_detail'], [
                    'business_partner_id' => $partner->getKey(),
                    'created_by' => 1,
                    'updated_by' => 1,
                ]));
            }

            if (isset($data['carrier_detail'])) {
                CarrierDetail::create(array_merge($data['carrier_detail'], [
                    'business_partner_id' => $partner->getKey(),
                    'created_by' => 1,
                    'updated_by' => 1,
                ]));
            }

            if (isset($data['carrier_vehicles'])) {
                foreach ($data['carrier_vehicles'] as $vehicle) {
                    CarrierVehicle::create(array_merge($vehicle, [
                        'business_partner_id' => $partner->getKey(),
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]));
                }
            }

            $created++;
        }

        $this->reportCreated($created);
    }
}
