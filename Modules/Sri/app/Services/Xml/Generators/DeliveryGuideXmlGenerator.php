<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Generators;

use DOMDocument;
use DOMElement;
use Modules\Core\Models\Company;
use Modules\Sales\Models\DeliveryGuide;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Services\Xml\BaseXmlGenerator;
use Modules\Sri\Services\Xml\Mappers\DeliveryGuideXmlMapper;

final class DeliveryGuideXmlGenerator extends BaseXmlGenerator
{
    public function __construct(private readonly DeliveryGuideXmlMapper $mapper) {}

    protected function getRootElement(): string
    {
        return 'guiaRemision';
    }

    protected function getVersion(): string
    {
        return '1.1.0';
    }

    /**
     * @param  DeliveryGuide&HasElectronicBilling  $document
     *
     * @throws XmlGenerationException
     */
    protected function buildBody(DOMDocument $dom, DOMElement $root, HasElectronicBilling $document): void
    {
        if (! $document instanceof DeliveryGuide) {
            throw new XmlGenerationException(__('DeliveryGuideXmlGenerator requires a DeliveryGuide model.'));
        }

        /** @var Company $company */
        $company = $document->getRelation('company');
        $data = $this->mapper->map($document, $company);

        // 1. <infoTributaria>
        $this->buildInfoTributaria($dom, $root, $data->issuer);

        // 2. <infoGuiaRemision>
        $infoGR = $dom->createElement('infoGuiaRemision');
        $root->appendChild($infoGR);

        $this->addTextElement($dom, $infoGR, 'dirPartida', $data->dirPartida);
        $this->addTextElement($dom, $infoGR, 'razonSocialTransportista', $data->razonSocialTransportista);
        $this->addTextElement($dom, $infoGR, 'tipoIdentificacionTransportista', $data->tipoIdentificacionTransportista);
        $this->addTextElement($dom, $infoGR, 'rucTransportista', $data->rucTransportista);
        $this->addTextElement($dom, $infoGR, 'obligadoContabilidad', 'SI');

        if (filled($data->issuer->contribuyenteEspecial)) {
            $this->addTextElement($dom, $infoGR, 'contribuyenteEspecial', $data->issuer->contribuyenteEspecial);
        }

        $this->addTextElement($dom, $infoGR, 'fechaIniTransporte', $data->fechaIniTransporte);
        $this->addTextElement($dom, $infoGR, 'fechaFinTransporte', $data->fechaFinTransporte);
        $this->addTextElement($dom, $infoGR, 'placa', $data->placa);

        if (! is_null($data->ruta)) {
            $this->addTextElement($dom, $infoGR, 'ruta', $data->ruta);
        }

        // 3. <destinatarios>
        $destinatariosEl = $dom->createElement('destinatarios');
        $root->appendChild($destinatariosEl);

        $destEl = $dom->createElement('destinatario');
        $destinatariosEl->appendChild($destEl);

        $this->addTextElement($dom, $destEl, 'identificacionDestinatario', $data->identificacionDestinatario);
        $this->addTextElement($dom, $destEl, 'razonSocialDestinatario', $data->razonSocialDestinatario);
        $this->addTextElement($dom, $destEl, 'dirDestinatario', $data->dirDestinatario);
        $this->addTextElement($dom, $destEl, 'motivoTraslado', $data->motivoTraslado);

        if (! blank($data->docAduaneroUnico)) {
            $this->addTextElement($dom, $destEl, 'docAduaneroUnico', $data->docAduaneroUnico);
        }

        // Items (detalles)
        $detallesEl = $dom->createElement('detalles');
        $destEl->appendChild($detallesEl);

        foreach ($data->detalles as $item) {
            $detalleEl = $dom->createElement('detalle');
            $detallesEl->appendChild($detalleEl);

            $this->addTextElement($dom, $detalleEl, 'codigoInterno', $item->codigoInterno);

            if (! is_null($item->codigoAdicional)) {
                $this->addTextElement($dom, $detalleEl, 'codigoAdicional', $item->codigoAdicional);
            }

            $this->addTextElement($dom, $detalleEl, 'descripcion', $item->descripcion);
            $this->addTextElement($dom, $detalleEl, 'cantidad', $item->cantidad);
        }

        // 4. <infoAdicional>
        $this->buildInfoAdicional($dom, $root, $data->infoAdicional);
    }
}
