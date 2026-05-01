<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Mappers;

use Modules\Core\Models\Company;
use Modules\Sales\Models\DeliveryGuide;
use Modules\Sales\Models\DeliveryGuideItem;
use Modules\Sales\Models\DeliveryGuideRecipient;
use Modules\Sri\DTOs\DeliveryGuideXmlData;
use Modules\Sri\DTOs\SriAdditionalInfoData;
use Modules\Sri\DTOs\SriDeliveryGuideItemData;
use Modules\Sri\Services\Xml\Mappers\Concerns\BuildsIssuerData;

final class DeliveryGuideXmlMapper
{
    use BuildsIssuerData;

    public function map(DeliveryGuide $guide, Company $company): DeliveryGuideXmlData
    {
        $issuer = $this->buildIssuerData($guide, $company);

        $guide->loadMissing('recipients.items', 'carrier.carrierVehicles');

        /** @var DeliveryGuideRecipient|null $primaryRecipient */
        $primaryRecipient = $guide->recipients->first();

        $detalles = $guide->recipients
            ->flatMap(fn (DeliveryGuideRecipient $recipient) => $recipient->items)
            ->map(fn (DeliveryGuideItem $item) => $this->mapItem($item))
            ->all();
        $infoAdicional = $this->mapAdditionalInfo($guide->additional_info ?? []);

        $fechaIni = $guide->transport_start_date?->format('d/m/Y') ?? now()->format('d/m/Y');
        $fechaFin = $guide->transport_end_date?->format('d/m/Y') ?? $fechaIni;
        $carrierName = $guide->carrier_name ?: $guide->carrier?->legal_name;
        $carrierIdentification = $guide->carrier_identification ?: $guide->carrier?->identification_number;
        $carrierPlate = $guide->carrier_plate ?: $guide->carrier?->carrierVehicles->first()?->vehicle_plate;

        return new DeliveryGuideXmlData(
            issuer: $issuer,
            dirPartida: $guide->origin_address ?? '',
            razonSocialTransportista: $carrierName ?? '',
            tipoIdentificacionTransportista: '04', // RUC — transportistas son personas jurídicas
            rucTransportista: $carrierIdentification ?? '',
            placa: $carrierPlate ?? '',
            fechaIniTransporte: $fechaIni,
            fechaFinTransporte: $fechaFin,
            razonSocialDestinatario: $primaryRecipient?->recipient_name ?? '',
            identificacionDestinatario: $primaryRecipient?->recipient_identification ?? '',
            dirDestinatario: $primaryRecipient?->destination_address ?? '',
            motivoTraslado: $primaryRecipient?->transfer_reason?->getLabel() ?? '',
            docAduaneroUnico: $primaryRecipient?->customs_doc,
            detalles: $detalles,
            infoAdicional: $infoAdicional,
            ruta: $primaryRecipient?->route,
        );
    }

    private function mapItem(DeliveryGuideItem $item): SriDeliveryGuideItemData
    {
        return new SriDeliveryGuideItemData(
            codigoInterno: $item->product_code ?? 'SIN-CODIGO',
            codigoAdicional: null,
            descripcion: $item->product_name ?? $item->description ?? '',
            cantidad: number_format((float) $item->quantity, 6, '.', ''),
        );
    }

    /** @return list<SriAdditionalInfoData> */
    private function mapAdditionalInfo(array $additionalInfo): array
    {
        $result = [];

        foreach ($additionalInfo as $key => $value) {
            if (is_array($value)) {
                $result[] = new SriAdditionalInfoData(
                    nombre: $value['name'] ?? (string) $key,
                    valor: $value['value'] ?? '',
                );
            } else {
                $result[] = new SriAdditionalInfoData(
                    nombre: (string) $key,
                    valor: (string) $value,
                );
            }
        }

        return $result;
    }
}
