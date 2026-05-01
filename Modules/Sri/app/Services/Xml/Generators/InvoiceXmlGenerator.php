<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Generators;

use DOMDocument;
use DOMElement;
use Modules\Core\Models\Company;
use Modules\Sales\Models\Invoice;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Services\Xml\BaseXmlGenerator;
use Modules\Sri\Services\Xml\Mappers\InvoiceXmlMapper;

final class InvoiceXmlGenerator extends BaseXmlGenerator
{
    public function __construct(private readonly InvoiceXmlMapper $mapper) {}

    protected function getRootElement(): string
    {
        return 'factura';
    }

    protected function getVersion(): string
    {
        return '2.1.0';
    }

    /**
     * @param  Invoice&HasElectronicBilling  $document
     *
     * @throws XmlGenerationException
     */
    protected function buildBody(DOMDocument $dom, DOMElement $root, HasElectronicBilling $document): void
    {
        if (! $document instanceof Invoice) {
            throw new XmlGenerationException('InvoiceXmlGenerator requires an Invoice model.');
        }

        /** @var Company $company */
        $company = $document->getRelation('company');
        $data = $this->mapper->map($document, $company);

        // 1. <infoTributaria>
        $this->buildInfoTributaria($dom, $root, $data->issuer);

        // 2. <infoFactura>
        $infoFactura = $dom->createElement('infoFactura');
        $root->appendChild($infoFactura);

        $this->addTextElement($dom, $infoFactura, 'fechaEmision', $data->fechaEmision);
        $this->addTextElement($dom, $infoFactura, 'dirEstablecimiento', $data->issuer->dirEstablecimiento);

        if (filled($data->issuer->contribuyenteEspecial)) {
            $this->addTextElement($dom, $infoFactura, 'contribuyenteEspecial', $data->issuer->contribuyenteEspecial);
        }

        $this->addTextElement($dom, $infoFactura, 'obligadoContabilidad', $data->obligadoContabilidad ? 'SI' : 'NO');
        $this->addTextElement($dom, $infoFactura, 'tipoIdentificacionComprador', $data->recipient->tipoIdentificacion);

        if (! is_null($data->guiaRemision)) {
            $this->addTextElement($dom, $infoFactura, 'guiaRemision', $data->guiaRemision);
        }

        $this->addTextElement($dom, $infoFactura, 'razonSocialComprador', $data->recipient->razonSocial);
        $this->addTextElement($dom, $infoFactura, 'identificacionComprador', $data->recipient->identificacion);

        if (filled($data->recipient->direccion)) {
            $this->addTextElement($dom, $infoFactura, 'direccionComprador', $data->recipient->direccion);
        }

        $this->addTextElement($dom, $infoFactura, 'totalSinImpuestos', $data->totalSinImpuestos);
        $this->addTextElement($dom, $infoFactura, 'totalDescuento', $data->totalDescuento);

        $this->buildTotalesConImpuestos($dom, $infoFactura, $data->totalesConImpuestos);

        $this->addTextElement($dom, $infoFactura, 'propina', $data->propina);
        $this->addTextElement($dom, $infoFactura, 'importeTotal', $data->importeTotal);
        $this->addTextElement($dom, $infoFactura, 'moneda', $data->moneda);

        if (! empty($data->pagos)) {
            $this->buildPagos($dom, $infoFactura, $data->pagos);
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

            if (! is_null($item->detalle1)) {
                $detallePropEl = $dom->createElement('detallesAdicionales');
                $detalleEl->appendChild($detallePropEl);
                $detAdEl = $dom->createElement('detAdicional');
                $detAdEl->setAttribute('nombre', 'Detalle1');
                $detAdEl->setAttribute('valor', $item->detalle1);
                $detallePropEl->appendChild($detAdEl);

                if (! is_null($item->detalle2)) {
                    $detAd2El = $dom->createElement('detAdicional');
                    $detAd2El->setAttribute('nombre', 'Detalle2');
                    $detAd2El->setAttribute('valor', $item->detalle2);
                    $detallePropEl->appendChild($detAd2El);
                }
            }

            $this->buildImpuestosDetalle($dom, $detalleEl, $item->impuestos);
        }

        // 4. <infoAdicional>
        $this->buildInfoAdicional($dom, $root, $data->infoAdicional);
    }
}
