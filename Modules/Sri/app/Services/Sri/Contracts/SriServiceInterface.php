<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Sri\Contracts;

interface SriServiceInterface
{
    public function consultarContribuyente(string $identificacion): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function consultarEstablecimientosPorRuc(string $ruc): array;

    public function existeRuc(string $ruc): bool;
}
