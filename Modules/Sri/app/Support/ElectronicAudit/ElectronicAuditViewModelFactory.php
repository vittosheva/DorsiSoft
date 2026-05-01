<?php

declare(strict_types=1);

namespace Modules\Sri\Support\ElectronicAudit;

use Illuminate\Database\Eloquent\Collection;

final class ElectronicAuditViewModelFactory
{
    /**
     * @param  array<string, mixed>  $auditData
     * @return array<string, mixed>
     */
    public function make(array $auditData): array
    {
        $summary = is_array($auditData['summary'] ?? null) ? $auditData['summary'] : [];
        $events = $auditData['events'] instanceof Collection ? $auditData['events'] : new Collection;
        $exchanges = $auditData['exchanges'] instanceof Collection ? $auditData['exchanges'] : new Collection;

        return [
            'summary_items' => [
                $this->makeSummaryItem(__('Detailed status'), $summary['status'] ?? null),
                $this->makeSummaryItem(__('Access key'), $summary['access_key'] ?? null, monospace: true),
                $this->makeSummaryItem(__('Authorization number'), $summary['authorization_number'] ?? null, monospace: true),
                $this->makeSummaryItem(__('Authorization date'), $summary['authorization_date'] ?? null),
                $this->makeSummaryItem(__('Submission date'), $summary['submitted_at'] ?? null),
                $this->makeSummaryItem(__('Internal authorization date'), $summary['authorized_at'] ?? null),
            ],
            'latest_error' => filled($summary['latest_error'] ?? null) ? (string) $summary['latest_error'] : '—',
            'events' => $events->map(fn ($event): array => $this->makeEventItem($event))->all(),
            'exchanges' => $exchanges->map(fn ($exchange): array => $this->makeExchangeItem($exchange))->all(),
            'xml_path' => filled($summary['xml_path'] ?? null) ? (string) $summary['xml_path'] : '—',
            'ride_path' => filled($summary['ride_path'] ?? null) ? (string) $summary['ride_path'] : '—',
            'xml_preview' => filled($auditData['xml_preview'] ?? null) ? (string) $auditData['xml_preview'] : '—',
            'events_count' => $events->count(),
            'exchanges_count' => $exchanges->count(),
        ];
    }

    private function makeSummaryItem(string $label, mixed $value, bool $monospace = false): array
    {
        return [
            'label' => $label,
            'value' => filled($value) ? (string) $value : '—',
            'monospace' => $monospace,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function makeEventItem(object $event): array
    {
        $messages = $event->payload['mensajes'] ?? null;
        $error = $event->payload['error'] ?? null;
        $authorizationNumber = $event->payload['authorization_number'] ?? null;
        $authorizationDate = $event->payload['authorization_date'] ?? null;

        $detailLines = match (true) {
            filled($authorizationNumber) => [
                (string) $authorizationNumber,
                filled($authorizationDate) ? (string) $authorizationDate : '—',
            ],
            filled($messages) => array_map(static fn (mixed $message): string => (string) $message, (array) $messages),
            filled($error) => [(string) $error],
            default => ['—'],
        };

        return [
            'label' => $this->eventLabel((string) $event->event),
            'color' => $this->eventColor((string) $event->event),
            'status_transition' => $this->formatStatusTransition($event->status_from ?? null, $event->status_to ?? null),
            'user' => filled($event->triggeredBy?->name ?? null) ? (string) $event->triggeredBy->name : __('System'),
            'created_at' => $event->created_at?->format('d/m/Y H:i:s') ?? '—',
            'detail_lines' => $detailLines,
            'detail_is_error' => filled($error),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function makeExchangeItem(object $exchange): array
    {
        return [
            'status' => mb_strtoupper((string) ($exchange->status ?? '—')),
            'status_color' => $this->exchangeColor((string) ($exchange->status ?? '')),
            'service' => filled($exchange->service ?? null) ? (string) $exchange->service : '—',
            'operation' => filled($exchange->operation ?? null) ? (string) $exchange->operation : '—',
            'created_at' => $exchange->created_at?->format('d/m/Y H:i:s') ?? '—',
            'environment' => filled($exchange->environment ?? null) ? (string) $exchange->environment : '—',
            'endpoint' => filled($exchange->endpoint ?? null) ? (string) $exchange->endpoint : '—',
            'duration' => $exchange->duration_ms !== null ? $exchange->duration_ms.' ms' : '—',
            'user' => filled($exchange->triggeredBy?->name ?? null) ? (string) $exchange->triggeredBy->name : __('System'),
            'error_message' => filled($exchange->error_message ?? null) ? (string) $exchange->error_message : null,
            'sri_error_detail' => $this->extractSriErrorDetail($exchange->response_body ?? null),
            'response_summary' => json_encode($exchange->response_summary ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—',
        ];
    }

    private function extractSriErrorDetail(mixed $responseBody): ?string
    {
        if (! is_string($responseBody) || $responseBody === '') {
            return null;
        }

        if (! preg_match('/<informacionAdicional>(.*?)<\/informacionAdicional>/s', $responseBody, $matches)) {
            return null;
        }

        $decoded = html_entity_decode((string) ($matches[1] ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $normalized = preg_replace('/\s+/', ' ', mb_trim($decoded));

        return is_string($normalized) && $normalized !== '' ? $normalized : null;
    }

    private function formatStatusTransition(?string $statusFrom, ?string $statusTo): string
    {
        if (filled($statusFrom) && filled($statusTo) && $statusFrom !== $statusTo) {
            return "{$statusFrom} → {$statusTo}";
        }

        if (filled($statusTo)) {
            return $statusTo;
        }

        return '—';
    }

    private function eventColor(string $event): string
    {
        return match ($event) {
            'authorized', 'poll_authorized' => 'success',
            'rejected', 'poll_rejected', 'failed' => 'danger',
            'submitted', 'poll_in_process' => 'warning',
            'xml_signed', 'xml_generated', 'xsd_validated' => 'info',
            default => 'gray',
        };
    }

    private function exchangeColor(string $status): string
    {
        return match ($status) {
            'authorized', 'received' => 'success',
            'failed', 'rejected' => 'danger',
            default => 'warning',
        };
    }

    private function eventLabel(string $event): string
    {
        return match ($event) {
            'process_started' => __('Process initiated'),
            'xml_generated' => __('XML generated'),
            'xsd_validated' => __('XML validated (XSD)'),
            'xml_signed' => __('XML signed'),
            'xml_stored' => __('XML stored'),
            'submitted' => __('Sent to SRI'),
            'authorized' => __('Authorized'),
            'rejected' => __('Rejected'),
            'poll_authorized' => __('Authorized (polling)'),
            'poll_rejected' => __('Rejected (polling)'),
            'poll_in_process' => __('In process (polling)'),
            'failed' => __('Failed'),
            default => $event,
        };
    }
}
