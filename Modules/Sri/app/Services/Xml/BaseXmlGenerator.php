<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml;

use DOMDocument;
use DOMElement;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Contracts\XmlGeneratorContract;
use Modules\Sri\DTOs\SriAdditionalInfoData;
use Modules\Sri\DTOs\SriIssuerData;
use Modules\Sri\DTOs\SriPaymentData;
use Modules\Sri\DTOs\SriTaxLineData;
use Modules\Sri\Exceptions\XmlGenerationException;

abstract class BaseXmlGenerator implements XmlGeneratorContract
{
    abstract protected function getRootElement(): string;

    abstract protected function getVersion(): string;

    /**
     * @throws XmlGenerationException
     */
    abstract protected function buildBody(DOMDocument $dom, DOMElement $root, HasElectronicBilling $document): void;

    /**
     * @throws XmlGenerationException
     */
    final public function generate(HasElectronicBilling $document): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;

        $root = $dom->createElement($this->getRootElement());
        $root->setAttribute('id', 'comprobante');
        $root->setAttribute('version', $this->getVersion());
        $dom->appendChild($root);

        $this->buildBody($dom, $root, $document);

        $xml = $dom->saveXML();

        if ($xml === false) {
            throw new XmlGenerationException(__('Failed to serialize XML document.'));
        }

        return $xml;
    }

    // ─── Shared helpers ───────────────────────────────────────────────────────

    protected function addTextElement(DOMDocument $dom, DOMElement $parent, string $tagName, string $value): DOMElement
    {
        $el = $dom->createElement($tagName);
        $el->appendChild($dom->createTextNode($value));
        $parent->appendChild($el);

        return $el;
    }

    protected function buildInfoTributaria(DOMDocument $dom, DOMElement $root, SriIssuerData $issuer): void
    {
        $info = $dom->createElement('infoTributaria');
        $root->appendChild($info);

        $this->addTextElement($dom, $info, 'ambiente', $issuer->ambiente);
        $this->addTextElement($dom, $info, 'tipoEmision', $issuer->tipoEmision);
        $this->addTextElement($dom, $info, 'razonSocial', $issuer->razonSocial);
        $this->addTextElement($dom, $info, 'nombreComercial', $issuer->nombreComercial);
        $this->addTextElement($dom, $info, 'ruc', $issuer->ruc);
        $this->addTextElement($dom, $info, 'claveAcceso', $issuer->claveAcceso);
        $this->addTextElement($dom, $info, 'codDoc', $issuer->codDoc);
        $this->addTextElement($dom, $info, 'estab', $issuer->estab);
        $this->addTextElement($dom, $info, 'ptoEmi', $issuer->ptoEmi);
        $this->addTextElement($dom, $info, 'secuencial', $issuer->secuencial);
        $this->addTextElement($dom, $info, 'dirMatriz', $issuer->dirMatriz);
    }

    /**
     * Builds <totalConImpuestos> section.
     *
     * @param  list<SriTaxLineData>  $totales
     */
    protected function buildTotalesConImpuestos(DOMDocument $dom, DOMElement $parent, array $totales): void
    {
        if (empty($totales)) {
            throw new XmlGenerationException(
                'El documento no tiene totales de impuestos. El nodo <totalConImpuestos> requiere al menos un hijo <totalImpuesto>.'
            );
        }

        $totalesEl = $dom->createElement('totalConImpuestos');
        $parent->appendChild($totalesEl);

        foreach ($totales as $tax) {
            $taxEl = $dom->createElement('totalImpuesto');
            $totalesEl->appendChild($taxEl);

            $this->addTextElement($dom, $taxEl, 'codigo', $tax->codigo);
            $this->addTextElement($dom, $taxEl, 'codigoPorcentaje', $tax->codigoPorcentaje);
            $this->addTextElement($dom, $taxEl, 'descuentoAdicional', '0.00');
            $this->addTextElement($dom, $taxEl, 'baseImponible', $tax->baseImponible);
            $this->addTextElement($dom, $taxEl, 'tarifa', $tax->tarifa);
            $this->addTextElement($dom, $taxEl, 'valor', $tax->valor);
        }
    }

    /**
     * Builds <impuestos> section for an item detail.
     *
     * @param  list<SriTaxLineData>  $impuestos
     */
    protected function buildImpuestosDetalle(DOMDocument $dom, DOMElement $parent, array $impuestos): void
    {
        if (empty($impuestos)) {
            throw new XmlGenerationException(
                'Un ítem de detalle no tiene impuestos. El nodo <impuestos> requiere al menos un hijo <impuesto>.'
            );
        }

        $impEl = $dom->createElement('impuestos');
        $parent->appendChild($impEl);

        foreach ($impuestos as $tax) {
            $taxEl = $dom->createElement('impuesto');
            $impEl->appendChild($taxEl);

            $this->addTextElement($dom, $taxEl, 'codigo', $tax->codigo);
            $this->addTextElement($dom, $taxEl, 'codigoPorcentaje', $tax->codigoPorcentaje);
            $this->addTextElement($dom, $taxEl, 'tarifa', $tax->tarifa);
            $this->addTextElement($dom, $taxEl, 'baseImponible', $tax->baseImponible);
            $this->addTextElement($dom, $taxEl, 'valor', $tax->valor);
        }
    }

    /**
     * Builds <infoAdicional> section.
     *
     * @param  list<SriAdditionalInfoData>  $infoAdicional
     */
    protected function buildInfoAdicional(DOMDocument $dom, DOMElement $root, array $infoAdicional): void
    {
        if (empty($infoAdicional)) {
            return;
        }

        $infoEl = $dom->createElement('infoAdicional');
        $root->appendChild($infoEl);

        foreach ($infoAdicional as $info) {
            $campo = $dom->createElement('campoAdicional');
            $campo->setAttribute('nombre', $info->nombre);
            $campo->appendChild($dom->createTextNode($info->valor));
            $infoEl->appendChild($campo);
        }
    }

    /**
     * Builds <pagos> section.
     *
     * @param  list<SriPaymentData>  $pagos
     */
    protected function buildPagos(DOMDocument $dom, DOMElement $parent, array $pagos): void
    {
        $pagosEl = $dom->createElement('pagos');
        $parent->appendChild($pagosEl);

        foreach ($pagos as $pago) {
            $pagoEl = $dom->createElement('pago');
            $pagosEl->appendChild($pagoEl);

            $this->addTextElement($dom, $pagoEl, 'formaPago', $pago->formaPago);
            $this->addTextElement($dom, $pagoEl, 'total', $pago->total);
            $this->addTextElement($dom, $pagoEl, 'plazo', $pago->plazo);
            $this->addTextElement($dom, $pagoEl, 'unidadTiempo', $pago->unidadTiempo);
        }
    }
}
