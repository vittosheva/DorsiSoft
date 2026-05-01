<?php

declare(strict_types=1);

namespace Modules\Sri\Support\ElectronicAudit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Sri\Concerns\HasElectronicEvents;
use Modules\Sri\Concerns\HasSriTechnicalExchanges;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Models\SriElectronicEvent;
use Modules\Sri\Models\SriTechnicalExchange;

final class ElectronicAuditDataResolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(?Model $record): array
    {
        if ($record === null || ! $record instanceof HasElectronicBilling) {
            return $this->emptyPayload();
        }

        $metadata = is_array($record->metadata ?? null) ? $record->metadata : [];
        $events = $this->resolveEvents($record);
        $exchanges = $this->resolveExchanges($record);

        return [
            'summary' => [
                'status' => $record->getElectronicStatus()?->getLabel() ?? __('Pending'),
                'access_key' => $record->getAccessKey(),
                'authorization_number' => $metadata['authorization_number'] ?? null,
                'authorization_date' => $metadata['authorization_date'] ?? null,
                'submitted_at' => $record->electronic_submitted_at?->format('d/m/Y H:i:s'),
                'authorized_at' => $record->electronic_authorized_at?->format('d/m/Y H:i:s'),
                'xml_path' => $metadata['xml_path'] ?? null,
                'ride_path' => $metadata['ride_path'] ?? null,
                'latest_error' => $metadata['error'] ?? null,
            ],
            'events' => $events,
            'exchanges' => $exchanges,
            'xml_preview' => $exchanges->firstWhere('operation', 'validarComprobante')?->request_body,
            'has_data' => $events->isNotEmpty() || $exchanges->isNotEmpty() || filled($metadata),
        ];
    }

    /**
     * @param  Model&HasElectronicBilling  $record
     * @return Collection<int, SriElectronicEvent>
     */
    private function resolveEvents(Model $record): Collection
    {
        if (! in_array(HasElectronicEvents::class, class_uses_recursive($record), true)) {
            return new Collection;
        }

        if ($record->relationLoaded('electronicEvents')) {
            /** @var Collection<int, SriElectronicEvent> $events */
            $events = $record->getRelation('electronicEvents');

            return new Collection($events->sortByDesc('created_at')->values()->all());
        }

        return $record
            ->electronicEvents()
            ->with(['triggeredBy:id,name'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  Model&HasElectronicBilling  $record
     * @return Collection<int, SriTechnicalExchange>
     */
    private function resolveExchanges(Model $record): Collection
    {
        if (! in_array(HasSriTechnicalExchanges::class, class_uses_recursive($record), true)) {
            return new Collection;
        }

        if ($record->relationLoaded('technicalExchanges')) {
            /** @var Collection<int, SriTechnicalExchange> $exchanges */
            $exchanges = $record->getRelation('technicalExchanges');

            return new Collection($exchanges->sortByDesc('created_at')->values()->all());
        }

        return $record
            ->technicalExchanges()
            ->with(['triggeredBy:id,name'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        return [
            'summary' => [
                'status' => null,
                'access_key' => null,
                'authorization_number' => null,
                'authorization_date' => null,
                'submitted_at' => null,
                'authorized_at' => null,
                'xml_path' => null,
                'ride_path' => null,
                'latest_error' => null,
            ],
            'events' => new Collection,
            'exchanges' => new Collection,
            'xml_preview' => null,
            'has_data' => false,
        ];
    }
}
