<?php

declare(strict_types=1);

namespace Modules\Sri\Contracts;

use Modules\Sri\Enums\ElectronicStatusEnum;

interface HasElectronicBilling
{
    /** Código oficial SRI del tipo de comprobante (e.g. '01', '04'). */
    public function getSriDocumentTypeCode(): string;

    public function getElectronicStatus(): ?ElectronicStatusEnum;

    public function getAccessKey(): ?string;

    /**
     * Relations to eager-load before XML generation.
     *
     * @return list<string>
     */
    public function getElectronicEagerLoads(): array;

    /**
     * Storage path for the signed XML file.
     * Pattern: tenants/{ruc}/documents/xml/{type}/{year}/{filename}.xml
     */
    public function getXmlStoragePath(string $tenantRuc): string;

    /**
     * Storage path for the authorized RIDE XML returned by SRI.
     * Pattern: tenants/{ruc}/documents/ride/{type}/{year}/{filename}.xml
     */
    public function getRideStoragePath(string $tenantRuc): string;
}
