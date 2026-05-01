<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/**
 * Datos para generar el XML de una Guía de Remisión (cod. 06).
 * Versión XSD: 1.1.0
 */
final readonly class DeliveryGuideXmlData
{
    /**
     * @param  list<SriDeliveryGuideItemData>  $detalles
     * @param  list<SriAdditionalInfoData>  $infoAdicional
     */
    public function __construct(
        public SriIssuerData $issuer,
        public string $dirPartida,
        public string $razonSocialTransportista,
        public string $tipoIdentificacionTransportista,
        public string $rucTransportista,
        public string $placa,
        public string $fechaIniTransporte,   // dd/MM/yyyy
        public string $fechaFinTransporte,   // dd/MM/yyyy
        public string $razonSocialDestinatario,
        public string $identificacionDestinatario,
        public string $dirDestinatario,
        public string $motivoTraslado,
        public ?string $docAduaneroUnico,
        public array $detalles,
        public array $infoAdicional,
        public ?string $ruta = null,
    ) {}
}
