<?php

declare(strict_types=1);

namespace Modules\Sri\Exceptions;

use Throwable;

final class SriReceptionException extends ElectronicBillingException
{
    /** @param list<string> $messages SRI response messages */
    public function __construct(
        public readonly array $messages = [],
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $summary = $message ?: 'SRI reception rejected the document: '.implode('; ', $messages);
        parent::__construct($summary, $code, $previous);
    }
}
