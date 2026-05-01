<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Detalle (línea de producto) para la sección <detalles> de factura y liquidación de compra. */
final readonly class SriInvoiceItemData
{
    /**
     * @param  list<SriTaxLineData>  $impuestos
     */
    public function __construct(
        public string $codigoPrincipal,
        public ?string $codigoAuxiliar,
        public string $descripcion,
        public string $cantidad,
        public string $precioUnitario,
        public string $descuento,
        public string $precioTotalSinImpuesto,
        public array $impuestos,
        public ?string $detalle1 = null,
        public ?string $detalle2 = null,
    ) {}
}
