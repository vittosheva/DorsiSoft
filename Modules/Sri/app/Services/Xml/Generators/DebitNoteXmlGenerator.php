<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Generators;

use DOMDocument;
use DOMElement;
use Modules\Core\Models\Company;
use Modules\Sales\Models\DebitNote;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Services\Xml\BaseXmlGenerator;
use Modules\Sri\Services\Xml\Mappers\DebitNoteXmlMapper;

final class DebitNoteXmlGenerator extends BaseXmlGenerator
{
    public function __construct(private readonly DebitNoteXmlMapper $mapper) {}

    protected function getRootElement(): string
    {
        return 'notaDebito';
    }

    protected function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * @param  DebitNote&HasElectronicBilling  $document
     *
     * @throws XmlGenerationException
     */
    protected function buildBody(DOMDocument $dom, DOMElement $root, HasElectronicBilling $document): void
    {
        if (! $document instanceof DebitNote) {
            throw new XmlGenerationException('DebitNoteXmlGenerator requires a DebitNote model.');
        }

        /** @var Company $company */
        $company = $document->getRelation('company');
        $data = $this->mapper->map($document, $company);

        // 1. <infoTributaria>
        $this->buildInfoTributaria($dom, $root, $data->issuer);

        // 2. <infoNotaDebito>
        $infoND = $dom->createElement('infoNotaDebito');
        $root->appendChild($infoND);

        $this->addTextElement($dom, $infoND, 'fechaEmision', $data->fechaEmision);
        $this->addTextElement($dom, $infoND, 'dirEstablecimiento', $data->issuer->dirEstablecimiento);

        $this->addTextElement($dom, $infoND, 'tipoIdentificacionComprador', $data->recipient->tipoIdentificacion);
        $this->addTextElement($dom, $infoND, 'razonSocialComprador', $data->recipient->razonSocial);
        $this->addTextElement($dom, $infoND, 'identificacionComprador', $data->recipient->identificacion);

        if (filled($data->issuer->contribuyenteEspecial)) {
            $this->addTextElement($dom, $infoND, 'contribuyenteEspecial', $data->issuer->contribuyenteEspecial);
        }

        $this->addTextElement($dom, $infoND, 'obligadoContabilidad', 'SI');
        $this->addTextElement($dom, $infoND, 'codDocModificado', $data->codDocSustento);
        $this->addTextElement($dom, $infoND, 'numDocModificado', $data->numDocSustento);
        $this->addTextElement($dom, $infoND, 'fechaEmisionDocSustento', $data->fechaEmisionDocSustento);
        $this->addTextElement($dom, $infoND, 'totalSinImpuestos', $data->totalSinImpuestos);

        // <impuestos>
        if (! empty($data->impuestos)) {
            $impEl = $dom->createElement('impuestos');
            $infoND->appendChild($impEl);

            foreach ($data->impuestos as $tax) {
                $taxEl = $dom->createElement('impuesto');
                $impEl->appendChild($taxEl);

                $this->addTextElement($dom, $taxEl, 'codigo', $tax->codigo);
                $this->addTextElement($dom, $taxEl, 'codigoPorcentaje', $tax->codigoPorcentaje);
                $this->addTextElement($dom, $taxEl, 'tarifa', $tax->tarifa);
                $this->addTextElement($dom, $taxEl, 'baseImponible', $tax->baseImponible);
                $this->addTextElement($dom, $taxEl, 'valor', $tax->valor);
            }
        }

        $this->addTextElement($dom, $infoND, 'valorTotal', $data->importeTotal);

        if (! empty($data->pagos)) {
            $this->buildPagos($dom, $infoND, $data->pagos);
        }

        // 3. XML reason list uses the SRI-required <motivos> tag names.
        $reasonsElement = $dom->createElement('motivos');
        $root->appendChild($reasonsElement);

        foreach ($data->reasons as $reason) {
            $reasonElement = $dom->createElement('motivo');
            $reasonsElement->appendChild($reasonElement);

            $this->addTextElement($dom, $reasonElement, 'razon', $reason->reason);
            $this->addTextElement($dom, $reasonElement, 'valor', $reason->value);
        }

        // 4. <infoAdicional>
        $this->buildInfoAdicional($dom, $root, $data->infoAdicional);
    }
}
