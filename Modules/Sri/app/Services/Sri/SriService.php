<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Sri;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Sri\Services\Sri\Contracts\SriServiceInterface;
use Throwable;

final class SriService implements SriServiceInterface
{
    public function __construct(private SriValidator $validator) {}

    public function consultarContribuyente(string $identificacion): array
    {
        $cleanIdentification = $this->validator->clean($identificacion);
        if ($cleanIdentification === '') {
            return [];
        }

        $cacheTtl = (int) $this->configValue('sri.cache_ttl', 3600);
        $cacheKey = "sri.contribuyente.{$cleanIdentification}";

        return Cache::remember(
            $cacheKey,
            $cacheTtl,
            fn () => $this->requestPayload(
                'sri.consultar_endpoint',
                'sri.query_param',
                $cleanIdentification,
            ),
        );
    }

    public function existeRuc(string $ruc): bool
    {
        if (! $this->validator->isValidRuc($ruc)) {
            return false;
        }

        return $this->consultarContribuyente($ruc) !== [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function consultarEstablecimientosPorRuc(string $ruc): array
    {
        $cleanRuc = $this->validator->clean($ruc);

        if (! $this->validator->isValidRuc($cleanRuc)) {
            return [];
        }

        $cacheTtl = (int) $this->configValue('sri.cache_ttl', 3600);
        $cacheKey = "sri.establecimientos.{$cleanRuc}";

        return Cache::remember($cacheKey, $cacheTtl, function () use ($cleanRuc): array {
            $data = $this->requestPayload(
                'sri.establecimientos_endpoint',
                'sri.establecimientos_query_param',
                $cleanRuc,
            );

            $rows = [];

            foreach ($data as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $rows[] = $item;
            }

            return $rows;
        });
    }

    private function requestPayload(string $endpointKey, string $queryParamKey, string $value): array
    {
        $endpoint = (string) $this->configValue($endpointKey, '/');
        $queryParam = (string) $this->configValue($queryParamKey, 'ruc');

        try {
            $response = Http::baseUrl((string) $this->configValue('sri.base_url'))
                ->acceptJson()
                ->timeout((int) $this->configValue('sri.timeout', 8))
                ->connectTimeout((int) $this->configValue('sri.connect_timeout', 3))
                ->get($endpoint, [$queryParam => $value]);

            if (! $response->successful()) {
                Log::warning('SRI request returned non-success status', [
                    'endpoint' => $endpoint,
                    'query_param' => $queryParam,
                    'value' => $value,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            if (! is_array($data)) {
                return [];
            }

            return $data;
        } catch (Throwable $exception) {
            Log::warning('SRI request failed', [
                'endpoint' => $endpoint,
                'query_param' => $queryParam,
                'value' => $value,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function configValue(string $key, mixed $default = null): mixed
    {
        return config()->get($key, $default);
    }
}
