<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Mappers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\Company;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\DTOs\SriIssuerData;
use Modules\Sri\Enums\SriEnvironmentEnum;
use Modules\Sri\Exceptions\XmlGenerationException;

trait BuildsIssuerData
{
    private function buildIssuerData(HasElectronicBilling $document, Company $company): SriIssuerData
    {
        /** @var Model&HasElectronicBilling $document */
        if (blank($company->tax_address)) {
            throw new XmlGenerationException(
                "La empresa [{$company->id}] no tiene dirección fiscal (tax_address). El campo 'dirMatriz' requiere mínimo 1 carácter."
            );
        }

        if (blank($company->legal_name)) {
            throw new XmlGenerationException(
                "La empresa [{$company->id}] no tiene razón social (legal_name). El campo 'razonSocial' requiere mínimo 1 carácter."
            );
        }

        $ambiente = match ($company->sri_environment) {
            SriEnvironmentEnum::TEST => '1',
            SriEnvironmentEnum::PRODUCTION => '2',
            default => '1',
        };

        return new SriIssuerData(
            ruc: $company->ruc,
            razonSocial: $this->sanitizeXsdString(mb_strtoupper($company->legal_name)),
            nombreComercial: $this->sanitizeXsdString(mb_strtoupper($company->trade_name ?? $company->legal_name)),
            dirMatriz: $this->sanitizeXsdString($company->tax_address),
            ambiente: $ambiente,
            tipoEmision: '1',
            codDoc: $document->getSriDocumentTypeCode(),
            estab: $document->establishment_code,
            ptoEmi: $document->emission_point_code,
            secuencial: $document->sequential_number,
            claveAcceso: $document->access_key ?? '',
            dirEstablecimiento: $this->sanitizeXsdString($company->tax_address),
            contribuyenteEspecial: ($company->is_special_taxpayer && filled($company->special_taxpayer_resolution))
                ? $this->sanitizeXsdString($company->special_taxpayer_resolution)
                : null,
        );
    }

    /**
     * Removes characters forbidden by XSD [^\n]* patterns (newlines, carriage returns, tabs).
     */
    private function sanitizeXsdString(string $value): string
    {
        return str_replace(["\n", "\r", "\t"], ' ', $value);
    }
}
