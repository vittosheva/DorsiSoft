<?php

declare(strict_types=1);

namespace Modules\Sri\Contracts;

use Modules\Sri\DTOs\SriAuthorizationResult;
use Modules\Sri\Enums\SriEnvironmentEnum;
use Modules\Sri\Exceptions\SriAuthorizationException;

interface SriAuthorizationServiceContract
{
    /**
     * Query the SRI authorization SOAP endpoint for the given access key.
     *
     * @throws SriAuthorizationException On SOAP fault or unexpected response
     */
    public function query(string $accessKey, SriEnvironmentEnum $environment): SriAuthorizationResult;
}
