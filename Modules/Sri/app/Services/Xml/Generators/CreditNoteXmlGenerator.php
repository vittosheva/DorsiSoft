<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Generators;

use DOMDocument;
use DOMElement;
use Modules\Core\Models\Company;
use Modules\Sales\Models\CreditNote;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\DTOs\SriTaxLineData;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Services\Xml\BaseXmlGenerator;
use Modules\Sri\Services\Xml\Mappers\CreditNoteXmlMapper;

final class CreditNoteXmlGenerator extends BaseXmlGenerator
{
    public function __construct(private readonly CreditNoteXmlMapper $mapper) {}

    protected function getRootElement(): string
    {
        return 'notaCredito';
    }

    protected function getVersion(): string
    {
        return '1.1.0';
    }

    /**
     * @param  CreditNote&HasElectronicBilling  $document
     *
     * @throws XmlGenerationException
     */
    protected function buildBody(DOMDocument $dom, DOMElement $root, HasElectronicBilling $document): void
    {
        if (! $document instanceof CreditNote) {
            throw new XmlGenerationException('CreditNoteXmlGenerator requires a CreditNote model.');
        }

        /** @var Company $company */
        $company = $document->getRelation('company');
        $data = $this->mapper->map($document, $company);

        // 1. <infoTributaria>
        $this->buildInfoTributaria($dom, $root, $data->issuer);

        // 2. <infoNotaCredito>
        $infoNC = $dom->createElement('infoNotaCredito');
        $root->appendChild($infoNC);

        $this->addTextElement($dom, $infoNC, 'fechaEmision', $data->fechaEmision);
        $this->addTextElement($dom, $infoNC, 'dirEstablecimiento', $data->issuer->dirEstablecimiento);
        $this->addTextElement($dom, $infoNC, 'tipoIdentificacionComprador', $data->recipient->tipoIdentificacion);
        $this->addTextElement($dom, $infoNC, 'razonSocialComprador', $data->recipient->razonSocial);
        $this->addTextElement($dom, $infoNC, 'identificacionComprador', $data->recipient->identificacion);

        if (filled($data->issuer->contribuyenteEspecial)) {
            $this->addTextElement($dom, $infoNC, 'contribuyenteEspecial', $data->issuer->contribuyenteEspecial);
        }

        $this->addTextElement($dom, $infoNC, 'obligadoContabilidad', $data->obligadoContabilidad ? 'SI' : 'NO');

        if (filled($data->rise)) {
            $this->addTextElement($dom, $infoNC, 'rise', $data->rise);
        }

        $this->addTextElement($dom, $infoNC, 'codDocModificado', $data->codDocSustento);
        $this->addTextElement($dom, $infoNC, 'numDocModificado', $data->numDocSustento);
        $this->addTextElement($dom, $infoNC, 'fechaEmisionDocSustento', $data->fechaEmisionDocSustento);
        $this->addTextElement($dom, $infoNC, 'totalSinImpuestos', $data->totalSinImpuestos);
        $this->addTextElement($dom, $infoNC, 'valorModificacion', $data->valorModificacion);
        $this->addTextElement($dom, $infoNC, 'moneda', $data->moneda);

        $this->buildCreditNoteTotalesConImpuestos($dom, $infoNC, $data->totalesConImpuestos);

        // The XML tag remains <motivo> per SRI, even though the internal DTO uses reason.
        $this->addTextElement($dom, $infoNC, 'motivo', $data->reason);

        // 3. <detalles>
        $detallesEl = $dom->createElement('detalles');
        $root->appendChild($detallesEl);

        foreach ($data->detalles as $item) {
            $detalleEl = $dom->createElement('detalle');
            $detallesEl->appendChild($detalleEl);

            $this->addTextElement($dom, $detalleEl, 'codigoInterno', $item->codigoPrincipal);

            if (! is_null($item->codigoAuxiliar)) {
                $this->addTextElement($dom, $detalleEl, 'codigoAdicional', $item->codigoAuxiliar);
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

    /**
     * @param  list<SriTaxLineData>  $totales
     */
    private function buildCreditNoteTotalesConImpuestos(DOMDocument $dom, DOMElement $parent, array $totales): void
    {
        if (empty($totales)) {
            throw new XmlGenerationException(
                'La nota de crédito no tiene totales de impuestos. El nodo <totalConImpuestos> requiere al menos un hijo <totalImpuesto>.'
            );
        }

        $totalesEl = $dom->createElement('totalConImpuestos');
        $parent->appendChild($totalesEl);

        foreach ($totales as $tax) {
            $taxEl = $dom->createElement('totalImpuesto');
            $totalesEl->appendChild($taxEl);

            $this->addTextElement($dom, $taxEl, 'codigo', $tax->codigo);
            $this->addTextElement($dom, $taxEl, 'codigoPorcentaje', $tax->codigoPorcentaje);
            $this->addTextElement($dom, $taxEl, 'baseImponible', $tax->baseImponible);
            $this->addTextElement($dom, $taxEl, 'valor', $tax->valor);
        }
    }
}
