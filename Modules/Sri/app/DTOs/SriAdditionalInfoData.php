<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Campo adicional para la sección <infoAdicional>. */
final readonly class SriAdditionalInfoData
{
    public function __construct(
        public string $nombre,
        public string $valor,
    ) {}
}
