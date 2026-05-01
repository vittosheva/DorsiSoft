<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

use Illuminate\Support\Carbon;

/**
 * Carries the contextual data needed by TaxRuleEngine to resolve applicable taxes.
 *
 * Fields use dot-notation paths that map to condition field names in fin_tax_rules.
 * Example conditions:
 *   {"field": "partner.identification_type", "operator": "=", "value": "04"}
 *   {"field": "product.category", "operator": "in", "value": ["services"]}
 */
final class TaxContext
{
    public function __construct(
        public readonly ?string $partnerIdentificationType = null,
        public readonly ?string $partnerTaxRegime = null,
        public readonly ?string $productCategory = null,
        public readonly ?string $productSriCode = null,
        public readonly ?string $documentType = null,
        public readonly ?string $companyRuc = null,
        public readonly ?Carbon $date = null,
    ) {}

    /**
     * Resolves a dot-notation field path to the corresponding context value.
     *
     * Supported paths:
     *  - partner.identification_type
     *  - partner.tax_regime
     *  - product.category
     *  - product.sri_code
     *  - document.type
     *  - company.ruc
     *  - date (YYYY-MM-DD string)
     */
    public function get(string $field): mixed
    {
        return match ($field) {
            'partner.identification_type' => $this->partnerIdentificationType,
            'partner.tax_regime' => $this->partnerTaxRegime,
            'product.category' => $this->productCategory,
            'product.sri_code' => $this->productSriCode,
            'document.type' => $this->documentType,
            'company.ruc' => $this->companyRuc,
            'date' => $this->date?->toDateString(),
            default => null,
        };
    }
}
