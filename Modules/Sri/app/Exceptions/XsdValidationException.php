<?php

declare(strict_types=1);

namespace Modules\Sri\Exceptions;

use Throwable;

final class XsdValidationException extends ElectronicBillingException
{
    /** @param list<string> $errors */
    public function __construct(
        public readonly array $errors,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $summary = $message ?: __('XML does not conform to the XSD schema: :errors', ['errors' => implode('; ', $errors)]);
        parent::__construct($summary, $code, $previous);
    }
}
