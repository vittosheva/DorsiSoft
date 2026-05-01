<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Generators;

use DOMDocument;
use DOMElement;
use Modules\Core\Models\Company;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Services\Xml\BaseXmlGenerator;
use Modules\Sri\Services\Xml\Mappers\PurchaseSettlementXmlMapper;

final class PurchaseSettlementXmlGenerator extends BaseXmlGenerator
{
    public function __construct(private readonly PurchaseSettlementXmlMapper $mapper) {}

    protected function getRootElement(): string
    {
        return 'liquidacionCompra';
    }

    protected function getVersion(): string
    {
        return '1.1.0';
    }

    /**
     * @param  PurchaseSettlement&HasElectronicBilling  $document
     *
     * @throws XmlGenerationException
     */
    protected function buildBody(DOMDocument $dom, DOMElement $root, HasElectronicBilling $document): void
    {
        if (! $document instanceof PurchaseSettlement) {
            throw new XmlGenerationException(__('PurchaseSettlementXmlGenerator requires a PurchaseSettlement model.'));
        }

        /** @var Company $company */
        $company = $document->getRelation('company');
        $data = $this->mapper->map($document, $company);

        // 1. <infoTributaria>
        $this->buildInfoTributaria($dom, $root, $data->issuer);

        // 2. <infoLiquidacionCompra>
        $infoLC = $dom->createElement('infoLiquidacionCompra');
        $root->appendChild($infoLC);

        $this->addTextElement($dom, $infoLC, 'fechaEmision', $data->fechaEmision);
        $this->addTextElement($dom, $infoLC, 'dirEstablecimiento', $data->issuer->dirEstablecimiento);

        if (filled($data->issuer->contribuyenteEspecial)) {
            $this->addTextElement($dom, $infoLC, 'contribuyenteEspecial', $data->issuer->contribuyenteEspecial);
        }

        $this->addTextElement($dom, $infoLC, 'obligadoContabilidad', $data->obligadoContabilidad ? 'SI' : 'NO');
        $this->addTextElement($dom, $infoLC, 'tipoIdentificacionProveedor', $data->recipient->tipoIdentificacion);
        $this->addTextElement($dom, $infoLC, 'razonSocialProveedor', $data->recipient->razonSocial);
        $this->addTextElement($dom, $infoLC, 'identificacionProveedor', $data->recipient->identificacion);
        $this->addTextElement($dom, $infoLC, 'totalSinImpuestos', $data->totalSinImpuestos);
        $this->addTextElement($dom, $infoLC, 'totalDescuento', $data->totalDescuento);

        $this->buildTotalesConImpuestos($dom, $infoLC, $data->totalesConImpuestos);

        $this->addTextElement($dom, $infoLC, 'importeTotal', $data->importeTotal);
        $this->addTextElement($dom, $infoLC, 'moneda', $data->moneda);

        if (! empty($data->pagos)) {
            $this->buildPagos($dom, $infoLC, $data->pagos);
        }

        // 3. <detalles>
        $detallesEl = $dom->createElement('detalles');
        $root->appendChild($detallesEl);

        foreach ($data->detalles as $item) {
            $detalleEl = $dom->createElement('detalle');
            $detallesEl->appendChild($detalleEl);

            $this->addTextElement($dom, $detalleEl, 'codigoPrincipal', $item->codigoPrincipal);

            if (! is_null($item->codigoAuxiliar)) {
                $this->addTextElement($dom, $detalleEl, 'codigoAuxiliar', $item->codigoAuxiliar);
            }

            $this->addTextElement($dom, $detalleEl, 'descripcion', $item->descripcion);
            $this->addTextElement($dom, $detalleEl, 'cantidad', $item->cantidad);
            $this->addTextElement($dom, $detalleEl, 'precioUnitario', $item->precioUnitario);
            $this->addTextElement($dom, $detalleEl, 'descuento', $item->descuento);
            $this->addTextElement($dom, $detalleEl, 'precioTotalSinImpuesto', $item->precioTotalSinImpuesto);

            $this->buildImpuestosDetalle($dom, $detalleEl, $item->impuestos);
        }

        // 4. <infoAdicional>
        $this->buildInfoAdicional($dom, $root, $data->infoAdicional);
    }
}
