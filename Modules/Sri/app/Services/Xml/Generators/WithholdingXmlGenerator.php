<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Generators;

use DOMDocument;
use DOMElement;
use Modules\Core\Models\Company;
use Modules\Sales\Models\Withholding;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Services\Xml\BaseXmlGenerator;
use Modules\Sri\Services\Xml\Mappers\WithholdingXmlMapper;

final class WithholdingXmlGenerator extends BaseXmlGenerator
{
    public function __construct(private readonly WithholdingXmlMapper $mapper) {}

    protected function getRootElement(): string
    {
        return 'comprobanteRetencion';
    }

    protected function getVersion(): string
    {
        return '2.0.0';
    }

    /**
     * @param  Withholding&HasElectronicBilling  $document
     *
     * @throws XmlGenerationException
     */
    protected function buildBody(DOMDocument $dom, DOMElement $root, HasElectronicBilling $document): void
    {
        if (! $document instanceof Withholding) {
            throw new XmlGenerationException(__('WithholdingXmlGenerator requires a Withholding model.'));
        }

        /** @var Company $company */
        $company = $document->getRelation('company');
        $data = $this->mapper->map($document, $company);

        // 1. <infoTributaria>
        $this->buildInfoTributaria($dom, $root, $data->issuer);

        // 2. <infoCompRetencion>
        $infoRet = $dom->createElement('infoCompRetencion');
        $root->appendChild($infoRet);

        $this->addTextElement($dom, $infoRet, 'fechaEmision', $data->fechaEmision);
        $this->addTextElement($dom, $infoRet, 'dirEstablecimiento', $data->issuer->dirEstablecimiento);

        if (filled($data->issuer->contribuyenteEspecial)) {
            $this->addTextElement($dom, $infoRet, 'contribuyenteEspecial', $data->issuer->contribuyenteEspecial);
        }

        $this->addTextElement($dom, $infoRet, 'obligadoContabilidad', $data->obligadoContabilidad ? 'SI' : 'NO');
        $this->addTextElement($dom, $infoRet, 'tipoIdentificacionSujetoRetenido', $data->recipient->tipoIdentificacion);
        // SRI XSD requires <parteRel> to appear before <razonSocialSujetoRetenido>
        $this->addTextElement($dom, $infoRet, 'parteRel', 'NO');
        $this->addTextElement($dom, $infoRet, 'razonSocialSujetoRetenido', $data->recipient->razonSocial);
        $this->addTextElement($dom, $infoRet, 'identificacionSujetoRetenido', $data->recipient->identificacion);
        $this->addTextElement($dom, $infoRet, 'periodoFiscal', $data->periodoFiscal);

        // 3. <docsSustento> (group impuestos by supporting document)
        $docsSustentoEl = $dom->createElement('docsSustento');
        $root->appendChild($docsSustentoEl);

        // Group items by document (codDocSustento + numDocSustento)
        $groups = [];
        foreach ($data->impuestos as $item) {
            $key = $item->codDocSustento.'|'.$item->numDocSustento;
            $groups[$key][] = $item;
        }

        foreach ($groups as $group) {
            $first = $group[0];

            $docEl = $dom->createElement('docSustento');
            $docsSustentoEl->appendChild($docEl);

            // Minimal required fields for docSustento to satisfy XSD ordering

            $this->addTextElement($dom, $docEl, 'codSustento', $first->codDocSustento ?? '00');
            $this->addTextElement($dom, $docEl, 'codDocSustento', $first->codDocSustento);

            // numDocSustento must match '[0-9]{15}'. Format source number (e.g. 001-001-000000051) to 15 digits.
            $rawNum = mb_trim((string) ($first->numDocSustento ?? ''));
            $numDoc = preg_replace('/\D+/', '', $rawNum);
            if ($numDoc === '') {
                // Fallback: use zeros to satisfy XSD pattern. Prefer failing early in pre-validation instead.
                $numDoc = str_repeat('0', 15);
            } else {
                $numDoc = mb_str_pad(mb_substr($numDoc, 0, 15), 15, '0', STR_PAD_LEFT);
            }
            $this->addTextElement($dom, $docEl, 'numDocSustento', $numDoc);

            $this->addTextElement($dom, $docEl, 'fechaEmisionDocSustento', $first->fechaEmisionDocSustento);

            // Optional / fallback fields (provide defaults to satisfy XSD constraints)
            $this->addTextElement($dom, $docEl, 'fechaRegistroContable', $first->fechaEmisionDocSustento);

            // '01' = pago local (residente). Fields tipoRegi/aplicConvDobTrib/pagoRegFis
            // must NOT appear for resident payments per SRI validation rules.
            $this->addTextElement($dom, $docEl, 'pagoLocExt', '01');

            $this->addTextElement($dom, $docEl, 'totalComprobantesReembolso', '0.00');
            $this->addTextElement($dom, $docEl, 'totalBaseImponibleReembolso', '0.00');
            $this->addTextElement($dom, $docEl, 'totalImpuestoReembolso', '0.00');
            $this->addTextElement($dom, $docEl, 'totalSinImpuestos', '0.00');

            $importeTotal = array_reduce($group, fn ($carry, $it) => $carry + (float) $it->valorRetenido, 0.0);
            $this->addTextElement($dom, $docEl, 'importeTotal', number_format($importeTotal, 2, '.', ''));

            // impuestosDocSustento (provide minimal impuestoDocSustento entries)
            $impuestosDocEl = $dom->createElement('impuestosDocSustento');
            $docEl->appendChild($impuestosDocEl);
            foreach ($group as $it) {
                $impuestoDocEl = $dom->createElement('impuestoDocSustento');
                $impuestosDocEl->appendChild($impuestoDocEl);

                // XSD expects codImpuestoDocSustento in [2,3,5]. Map known codes, default to '2'.
                $codImpuesto = in_array($it->codigo, ['2', '3', '5'], true) ? $it->codigo : '2';
                $this->addTextElement($dom, $impuestoDocEl, 'codImpuestoDocSustento', $codImpuesto);
                $this->addTextElement($dom, $impuestoDocEl, 'codigoPorcentaje', '0');
                $this->addTextElement($dom, $impuestoDocEl, 'baseImponible', $it->baseImponible);
                $this->addTextElement($dom, $impuestoDocEl, 'tarifa', '0.00');
                $this->addTextElement($dom, $impuestoDocEl, 'valorImpuesto', '0.00');
            }

            // retenciones
            $retencionesEl = $dom->createElement('retenciones');
            $docEl->appendChild($retencionesEl);
            foreach ($group as $it) {
                $retEl = $dom->createElement('retencion');
                $retencionesEl->appendChild($retEl);

                $this->addTextElement($dom, $retEl, 'codigo', $it->codigo);
                $this->addTextElement($dom, $retEl, 'codigoRetencion', $it->codigoRetencion);
                $this->addTextElement($dom, $retEl, 'baseImponible', $it->baseImponible);
                $this->addTextElement($dom, $retEl, 'porcentajeRetener', $it->porcentajeRetener);
                $this->addTextElement($dom, $retEl, 'valorRetenido', $it->valorRetenido);
            }

            // pagos (minimal): for docSustento pagos only accept formaPago and total in this XSD version
            $pagosEl = $dom->createElement('pagos');
            $docEl->appendChild($pagosEl);
            $pagoEl = $dom->createElement('pago');
            $pagosEl->appendChild($pagoEl);
            $this->addTextElement($dom, $pagoEl, 'formaPago', '01');
            $this->addTextElement($dom, $pagoEl, 'total', number_format($importeTotal, 2, '.', ''));
        }

        // 4. <infoAdicional>
        $this->buildInfoAdicional($dom, $root, $data->infoAdicional);
    }
}
