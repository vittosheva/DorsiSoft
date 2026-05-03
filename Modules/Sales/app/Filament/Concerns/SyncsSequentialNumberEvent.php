<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Livewire\Attributes\On;

/**
 * Este trait se encarga de escuchar el evento de actualización del número secuencial y actualizar el valor en el formulario.
 * Esto es necesario para que, cuando se actualice el número secuencial en el backend
 * (después de guardar el registro), el formulario se actualice automáticamente con el nuevo número secuencial sin necesidad de recargar la página.
 */
trait SyncsSequentialNumberEvent
{
    #[On('sequential-updated')]
    public function refreshSequentialNumber(array $data): void
    {
        // Recargar el valor
        $this->data['sequential_number'] = $data['sequential_number'];
    }
}
