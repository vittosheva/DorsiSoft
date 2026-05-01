<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Sri\Contracts\HasElectronicBilling;

final class ElectronicDocumentCorrectionClassifier
{
    public const REQUIRES_CORRECTION = 'requires_correction';

    public const RETRYABLE = 'retryable';

    /**
     * @var array<int, string>
     */
    private const RETRYABLE_KEYWORDS = [
        'TIMEOUT',
        'TIMED OUT',
        'CONEXION',
        'CONEXIÓN',
        'SOAP',
        'SERVICIO NO DISPONIBLE',
        'TEMPORALMENTE',
        'INTENTE NUEVAMENTE',
        'INTENTE MAS TARDE',
        'INTENTE MÁS TARDE',
        'UNAVAILABLE',
        'NO FUE POSIBLE CONECTARSE',
    ];

    public function classifyDocument(Model&HasElectronicBilling $document): string
    {
        return $this->classifyMessages($this->extractMessages($document));
    }

    /**
     * @param  array<int, mixed>  $messages
     */
    public function classifyMessages(array $messages): string
    {
        if ($messages === []) {
            return self::REQUIRES_CORRECTION;
        }

        $normalizedMessages = collect($messages)
            ->map(function (mixed $message): string {
                if (is_array($message)) {
                    $message = Arr::first($message, static fn (mixed $value): bool => is_string($value)) ?? json_encode($message);
                }

                return Str::upper(Str::ascii((string) $message));
            })
            ->filter()
            ->values();

        $isRetryable = $normalizedMessages->contains(function (string $message): bool {
            foreach (self::RETRYABLE_KEYWORDS as $keyword) {
                if (Str::contains($message, Str::upper(Str::ascii($keyword)))) {
                    return true;
                }
            }

            return false;
        });

        return $isRetryable ? self::RETRYABLE : self::REQUIRES_CORRECTION;
    }

    public function summarize(Model&HasElectronicBilling $document): ?string
    {
        $message = Arr::first($this->extractMessages($document));

        if (is_string($message)) {
            $message = mb_trim($message);

            return $message !== '' ? Str::limit($message, 500, '') : null;
        }

        if (is_array($message)) {
            $flattened = collect($message)
                ->flatten()
                ->filter(fn (mixed $value): bool => is_scalar($value))
                ->map(fn (mixed $value): string => (string) $value)
                ->implode(' | ');

            return $flattened !== '' ? Str::limit($flattened, 500, '') : null;
        }

        return null;
    }

    /**
     * @return array<int, mixed>
     */
    private function extractMessages(Model&HasElectronicBilling $document): array
    {
        $metadata = is_array($document->metadata ?? null) ? $document->metadata : [];

        $messages = data_get($metadata, 'reception_mensajes');

        if (is_array($messages) && $messages !== []) {
            return $messages;
        }

        $messages = data_get($metadata, 'authorization_mensajes');

        return is_array($messages) ? $messages : [];
    }
}
