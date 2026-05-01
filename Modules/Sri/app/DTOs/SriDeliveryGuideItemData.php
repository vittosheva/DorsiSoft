<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Detalle de bien transportado en la guía de remisión. */
final readonly class SriDeliveryGuideItemData
{
    public function __construct(
        public string $codigoInterno,
        public ?string $codigoAdicional,
        public string $descripcion,
        public string $cantidad,
    ) {}
}
