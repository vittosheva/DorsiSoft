<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Contrato para todos los enums de estado de documento.
 *
 * Permite escribir código genérico que opere sobre estados sin depender
 * de un enum concreto (ej: StatusFilter, HasDocumentBehavior).
 */
interface DocumentStatus
{
    public function isEditable(): bool;

    public function isVoided(): bool;

    public function getLabel(): string;

    public function getColor(): string|array|null;
}
