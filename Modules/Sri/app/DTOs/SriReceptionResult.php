<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Resultado de la recepción del comprobante por el SRI. */
final readonly class SriReceptionResult
{
    public function __construct(
        public string $estado,     // 'RECIBIDA' | 'DEVUELTA'
        /** @var list<string> */
        public array $mensajes = [],
        public ?string $requestXml = null,
        public ?string $responseXml = null,
        public ?string $endpoint = null,
    ) {}

    public function isReceived(): bool
    {
        return $this->estado === 'RECIBIDA';
    }
}
