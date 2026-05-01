<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Internal reason entry used to render the debit note XML <motivos> section. */
final readonly class DebitNoteReason
{
    public function __construct(
        public string $reason,
        public string $value,
    ) {}
}
