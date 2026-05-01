<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Resultado de la consulta de autorización al SRI. */
final readonly class SriAuthorizationResult
{
    public function __construct(
        public string $estado,                  // 'AUTORIZADO' | 'NO AUTORIZADO' | 'EN PROCESO'
        public ?string $numeroAutorizacion,
        public ?string $fechaAutorizacion,
        public ?string $ambiente,
        public ?string $comprobante,            // XML del RIDE autorizado
        /** @var list<string> */
        public array $mensajes = [],
        public ?string $requestXml = null,
        public ?string $responseXml = null,
        public ?string $endpoint = null,
    ) {}

    public function isAuthorized(): bool
    {
        return $this->estado === 'AUTORIZADO';
    }

    public function isRejected(): bool
    {
        return $this->estado === 'NO AUTORIZADO';
    }

    public function isInProcess(): bool
    {
        return $this->estado === 'EN PROCESO';
    }
}
