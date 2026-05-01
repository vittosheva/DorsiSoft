<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Sri\Concerns\HasSriTechnicalExchanges;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Models\SriTechnicalExchange;
use Throwable;

final class SriTechnicalExchangeLogger
{
    private const MAX_BODY_LENGTH = 50000;

    /**
     * @param  Model&HasElectronicBilling&HasSriTechnicalExchanges  $document
     * @param  array<string, mixed>  $requestSummary
     * @param  array<string, mixed>  $responseSummary
     */
    public static function record(
        HasElectronicBilling $document,
        string $service,
        string $operation,
        string $status,
        ?string $environment = null,
        ?string $endpoint = null,
        int $attempt = 1,
        array $requestSummary = [],
        array $responseSummary = [],
        ?string $requestBody = null,
        ?string $responseBody = null,
        ?int $durationMs = null,
        ?int $triggeredBy = null,
        ?Throwable $exception = null,
    ): ?SriTechnicalExchange {
        /** @var Model&HasElectronicBilling $document */
        if (! in_array(HasSriTechnicalExchanges::class, class_uses_recursive($document), true)) {
            return null;
        }

        return SriTechnicalExchange::create([
            'company_id' => $document->company_id,
            'documentable_type' => $document->getMorphClass(),
            'documentable_id' => $document->getKey(),
            'service' => $service,
            'operation' => $operation,
            'environment' => $environment,
            'endpoint' => $endpoint,
            'status' => $status,
            'attempt' => max($attempt, 1),
            'request_summary' => blank($requestSummary) ? null : $requestSummary,
            'response_summary' => blank($responseSummary) ? null : $responseSummary,
            'request_body' => self::truncate($requestBody),
            'response_body' => self::truncate($responseBody),
            'error_class' => $exception ? $exception::class : null,
            'error_message' => $exception?->getMessage(),
            'duration_ms' => $durationMs,
            'triggered_by' => $triggeredBy,
        ]);
    }

    private static function truncate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Str::of($value)
            ->limit(self::MAX_BODY_LENGTH, '... [truncated]')
            ->value();
    }
}
