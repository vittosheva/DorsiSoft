<?php

declare(strict_types=1);

namespace Modules\Sri\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Sri\Enums\SriCatalogTypeEnum;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\System\Enums\TaxGroupEnum;
use Modules\System\Enums\WithholdingAppliesToEnum;
use Modules\System\Models\SriCatalog;

final class SriCatalogSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $created = 0;
        $updated = 0;

        foreach ($this->records() as $record) {
            $model = SriCatalog::query()->updateOrCreate(
                [
                    'catalog_type' => $record['catalog_type'],
                    'code' => $record['code'],
                ],
                $record
            );

            $this->tallyModelChange($model, $created, $updated);
        }

        $this->reportCreatedAndUpdated($created, $updated);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function records(): array
    {
        return array_merge(
            $this->tipoComprobanteRecords(),
            $this->tipoIdentificacionRecords(),
            $this->formaPagoRecords(),
            $this->sustentoTributarioRecords(),
            $this->codigoRetencionRecords(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tipoComprobanteRecords(): array
    {
        return array_map(fn (SriDocumentTypeEnum $type) => [
            'catalog_type' => SriCatalogTypeEnum::TipoComprobante->value,
            'code' => $type->sriCode(),
            'name' => $type->sriName(),
            'description' => $type->sriDescription(),
            'extra_data' => null,
            'is_active' => true,
            'valid_from' => '2014-01-01',
            'valid_to' => null,
            'sort_order' => $type->sriCatalogSortOrder(),
        ], SriDocumentTypeEnum::cases());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tipoIdentificacionRecords(): array
    {
        return [
            // =========================================
            // TIPO DE IDENTIFICACIÓN
            // =========================================
            [
                'catalog_type' => SriCatalogTypeEnum::TipoIdentificacion->value,
                'code' => '04',
                'name' => 'RUC',
                'description' => 'Registro Único de Contribuyentes',
                'extra_data' => ['digits' => 13],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 1,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::TipoIdentificacion->value,
                'code' => '05',
                'name' => 'Cédula',
                'description' => 'Cédula de ciudadanía ecuatoriana',
                'extra_data' => ['digits' => 10],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 2,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::TipoIdentificacion->value,
                'code' => '06',
                'name' => 'Pasaporte',
                'description' => 'Pasaporte extranjero',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 3,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::TipoIdentificacion->value,
                'code' => '07',
                'name' => 'Consumidor Final',
                'description' => 'Venta a consumidor final (sin identificación)',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 4,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::TipoIdentificacion->value,
                'code' => '08',
                'name' => 'Identificación del Exterior',
                'description' => 'Identificación de persona natural o jurídica del exterior',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 5,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formaPagoRecords(): array
    {
        return [
            // =========================================
            // FORMA DE PAGO
            // =========================================
            [
                'catalog_type' => SriCatalogTypeEnum::FormaPago->value,
                'code' => '01',
                'name' => 'Sin utilización del sistema financiero',
                'description' => 'Efectivo u otros medios fuera del sistema financiero',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 1,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::FormaPago->value,
                'code' => '15',
                'name' => 'Compensación de deudas',
                'description' => 'Pago mediante compensación de deudas',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 2,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::FormaPago->value,
                'code' => '16',
                'name' => 'Tarjeta de débito',
                'description' => 'Pago con tarjeta de débito',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 3,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::FormaPago->value,
                'code' => '17',
                'name' => 'Dinero electrónico',
                'description' => 'Pago con dinero electrónico BCE',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 4,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::FormaPago->value,
                'code' => '18',
                'name' => 'Tarjeta prepago',
                'description' => 'Pago con tarjeta prepago',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 5,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::FormaPago->value,
                'code' => '19',
                'name' => 'Tarjeta de crédito',
                'description' => 'Pago con tarjeta de crédito',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 6,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::FormaPago->value,
                'code' => '20',
                'name' => 'Otros con utilización del sistema financiero',
                'description' => 'Transferencia bancaria, cheque u otros medios financieros',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 7,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::FormaPago->value,
                'code' => '21',
                'name' => 'Endoso de títulos',
                'description' => 'Pago mediante endoso de títulos',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 8,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sustentoTributarioRecords(): array
    {
        return [
            // =========================================
            // SUSTENTO TRIBUTARIO
            // =========================================
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '01',
                'name' => 'Crédito Tributario para declaración de IVA',
                'description' => 'Sustento para uso del crédito tributario en declaración de IVA',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 1,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '02',
                'name' => 'Costo o Gasto para declaración de IR',
                'description' => 'Gasto deducible para declaración de Impuesto a la Renta',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 2,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '03',
                'name' => 'Activo Fijo - Crédito Tributario para declaración de IVA',
                'description' => 'Adquisición de activo fijo con crédito tributario IVA',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 3,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '04',
                'name' => 'Activo Fijo - Costo o Gasto para declaración de IR',
                'description' => 'Adquisición de activo fijo deducible IR',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 4,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '05',
                'name' => 'Liquidación Gastos de Viaje, Hospedaje y Alimentación Gastos IR (activos fijos no)',
                'description' => 'Gastos de viaje, hospedaje y alimentación deducibles IR',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 5,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '06',
                'name' => 'Crédito Tributario para declaración de IR',
                'description' => 'Retención que constituye crédito tributario para declaración de IR',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 6,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '07',
                'name' => 'Exportador de Bienes',
                'description' => 'Adquisición para exportación de bienes',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 7,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '08',
                'name' => 'Factoring',
                'description' => 'Operaciones de factoring',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 8,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '09',
                'name' => 'Reembolso por Cuenta de Terceros - Intermediario',
                'description' => 'Reembolso de gastos por cuenta de terceros',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 9,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '10',
                'name' => 'Distribución de Dividendos, Beneficios o Utilidades',
                'description' => 'Distribución de dividendos y utilidades',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 10,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '11',
                'name' => 'Importaciones',
                'description' => 'Adquisición mediante importación directa',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 11,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '12',
                'name' => 'Régimen de Microempresas',
                'description' => 'Transacciones bajo régimen de microempresas',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2020-01-01',
                'valid_to' => null,
                'sort_order' => 12,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::SustentoTributario->value,
                'code' => '15',
                'name' => 'Pagos al Exterior',
                'description' => 'Pagos y remesas al exterior',
                'extra_data' => null,
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 13,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function codigoRetencionRecords(): array
    {
        $ir = TaxGroupEnum::Renta->value;
        $iva = TaxGroupEnum::Iva->value;

        $bienes = WithholdingAppliesToEnum::Bien->value;
        $servicios = WithholdingAppliesToEnum::Servicio->value;
        $ambos = WithholdingAppliesToEnum::Ambos->value;

        return [
            // =========================================
            // CÓDIGOS DE RETENCIÓN IR — BIENES Y SERVICIOS
            // =========================================
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '303',
                'name' => 'Honorarios profesionales y dietas',
                'description' => 'Retención por honorarios profesionales y dietas — 10%',
                'extra_data' => ['rate' => 10, 'applies_to' => $servicios, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 10,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '303A',
                'name' => 'Servicios de transporte privado de pasajeros o servicio público o privado de carga',
                'description' => 'Retención servicios de transporte — 1%',
                'extra_data' => ['rate' => 1, 'applies_to' => $servicios, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 11,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '303B',
                'name' => 'Por pagos a través de liquidación de compra (nivel cultural o rusticidad)',
                'description' => 'Retención en liquidaciones de compra — 2%',
                'extra_data' => ['rate' => 2, 'applies_to' => $bienes, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 12,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '304',
                'name' => 'Servicios donde predomina el intelecto no señalados en el artículo 9 del RLORTI',
                'description' => 'Retención servicios intelectuales — 8%',
                'extra_data' => ['rate' => 8, 'applies_to' => $servicios, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 13,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '307',
                'name' => 'Servicios predomina mano de obra',
                'description' => 'Retención servicios donde predomina la mano de obra — 2%',
                'extra_data' => ['rate' => 2, 'applies_to' => $servicios, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 14,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '308',
                'name' => 'Utilización o aprovechamiento de la imagen o renombre',
                'description' => 'Retención por uso de imagen o renombre — 10%',
                'extra_data' => ['rate' => 10, 'applies_to' => $servicios, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 15,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '309',
                'name' => 'Servicio de medios de comunicación y agencias de publicidad',
                'description' => 'Retención publicidad y medios — 1%',
                'extra_data' => ['rate' => 1, 'applies_to' => $servicios, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 16,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '310',
                'name' => 'Servicio de transporte de carga',
                'description' => 'Retención transporte de carga — 1%',
                'extra_data' => ['rate' => 1, 'applies_to' => $servicios, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 17,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '312',
                'name' => 'Transferencia de bienes muebles de naturaleza corporal',
                'description' => 'Retención compra de bienes muebles — 1%',
                'extra_data' => ['rate' => 1, 'applies_to' => $bienes, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 1,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '319',
                'name' => 'Arrendamiento de bienes inmuebles',
                'description' => 'Retención arrendamiento inmuebles — 8%',
                'extra_data' => ['rate' => 8, 'applies_to' => $servicios, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 18,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '320',
                'name' => 'Arrendamiento de bienes muebles',
                'description' => 'Retención arrendamiento muebles — 1.75%',
                'extra_data' => ['rate' => 1.75, 'applies_to' => $servicios, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 19,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '332',
                'name' => 'Seguros y reaseguros (primas y cesiones)',
                'description' => 'Retención seguros y reaseguros — 1%',
                'extra_data' => ['rate' => 1, 'applies_to' => $servicios, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 20,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '340',
                'name' => 'Otras retenciones aplicables el 1%',
                'description' => 'Otras retenciones al 1%',
                'extra_data' => ['rate' => 1, 'applies_to' => $ambos, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 90,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '341',
                'name' => 'Otras retenciones aplicables el 2%',
                'description' => 'Otras retenciones al 2%',
                'extra_data' => ['rate' => 2, 'applies_to' => $ambos, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 91,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '343',
                'name' => 'Otras retenciones aplicables el 8%',
                'description' => 'Otras retenciones al 8%',
                'extra_data' => ['rate' => 8, 'applies_to' => $ambos, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 92,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '344',
                'name' => 'Otras retenciones aplicables el 10%',
                'description' => 'Otras retenciones al 10%',
                'extra_data' => ['rate' => 10, 'applies_to' => $ambos, 'sri_group' => $ir],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 93,
            ],

            // =========================================
            // CÓDIGOS DE RETENCIÓN IVA
            // =========================================
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '725',
                'name' => 'Retención del 10% del IVA',
                'description' => 'Retención IVA 10% — bienes en contratos de construcción',
                'extra_data' => ['rate' => 10, 'applies_to' => $bienes, 'sri_group' => $iva],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 1,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '729',
                'name' => 'Retención del 20% del IVA',
                'description' => 'Retención IVA 20%',
                'extra_data' => ['rate' => 20, 'applies_to' => $ambos, 'sri_group' => $iva],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 2,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '731',
                'name' => 'Retención del 30% del IVA',
                'description' => 'Retención IVA 30% — bienes',
                'extra_data' => ['rate' => 30, 'applies_to' => $bienes, 'sri_group' => $iva],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 3,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '733',
                'name' => 'Retención del 50% del IVA',
                'description' => 'Retención IVA 50%',
                'extra_data' => ['rate' => 50, 'applies_to' => $ambos, 'sri_group' => $iva],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 4,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '735',
                'name' => 'Retención del 70% del IVA',
                'description' => 'Retención IVA 70% — servicios',
                'extra_data' => ['rate' => 70, 'applies_to' => $servicios, 'sri_group' => $iva],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 5,
            ],
            [
                'catalog_type' => SriCatalogTypeEnum::CodigoRetencion->value,
                'code' => '737',
                'name' => 'Retención del 100% del IVA',
                'description' => 'Retención IVA 100% — casos especiales y agentes de retención designados',
                'extra_data' => ['rate' => 100, 'applies_to' => $ambos, 'sri_group' => $iva],
                'is_active' => true,
                'valid_from' => '2014-01-01',
                'valid_to' => null,
                'sort_order' => 6,
            ],
        ];
    }
}
