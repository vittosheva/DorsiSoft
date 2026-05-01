<?php

declare(strict_types=1);

namespace Modules\Sri\Contracts;

use Modules\Sri\Exceptions\XmlGenerationException;

interface XmlGeneratorContract
{
    /**
     * Generate the SRI-compliant XML string for the given document.
     *
     * The document must already have its access_key populated.
     *
     * @throws XmlGenerationException
     */
    public function generate(HasElectronicBilling $document): string;
}
