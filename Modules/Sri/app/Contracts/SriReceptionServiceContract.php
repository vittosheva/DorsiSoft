<?php

declare(strict_types=1);

namespace Modules\Sri\Contracts;

use Modules\Sri\DTOs\SriReceptionResult;
use Modules\Sri\Enums\SriEnvironmentEnum;
use Modules\Sri\Exceptions\SriReceptionException;

interface SriReceptionServiceContract
{
    /**
     * Send a signed XML document to the SRI reception SOAP endpoint.
     *
     * @throws SriReceptionException On SOAP fault or unexpected response
     */
    public function send(string $signedXml, SriEnvironmentEnum $environment): SriReceptionResult;
}
