<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Soap;

use Modules\Sri\Contracts\SriAuthorizationServiceContract;
use Modules\Sri\DTOs\SriAuthorizationResult;
use Modules\Sri\Enums\SriEnvironmentEnum;
use Modules\Sri\Exceptions\SriAuthorizationException;
use SoapClient;
use SoapFault;
use Throwable;

final class SriAuthorizationService implements SriAuthorizationServiceContract
{
    /** @var array<string, string> */
    private array $wsdlUrls = [
        'pruebas' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
        'produccion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
    ];

    /** @throws SriAuthorizationException */
    public function query(string $accessKey, SriEnvironmentEnum $env): SriAuthorizationResult
    {
        $wsdl = $this->wsdlUrls[$env->value];

        try {
            $client = new SoapClient($wsdl, [
                'exceptions' => true,
                'trace' => true,
                'connection_timeout' => config('sri.electronic.soap_connect_timeout', 10),
                'stream_context' => stream_context_create([
                    'http' => ['timeout' => config('sri.electronic.soap_timeout', 30)],
                    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
                ]),
            ]);

            $response = $client->autorizacionComprobante([
                'claveAccesoComprobante' => $accessKey,
            ]);

            return $this->parseResponse(
                response: $response,
                requestXml: $client->__getLastRequest(),
                responseXml: $client->__getLastResponse(),
                endpoint: $wsdl,
            );
        } catch (SoapFault $e) {
            throw new SriAuthorizationException(
                "SRI Authorization SOAP fault: {$e->getMessage()}",
                previous: $e,
            );
        } catch (Throwable $e) {
            throw new SriAuthorizationException(
                "SRI Authorization failed: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /** @throws SriAuthorizationException */
    private function parseResponse(
        mixed $response,
        ?string $requestXml = null,
        ?string $responseXml = null,
        ?string $endpoint = null,
    ): SriAuthorizationResult {
        $result = $response->RespuestaAutorizacionComprobante ?? null;

        if ($result === null) {
            throw new SriAuthorizationException(__('Unexpected SRI authorization response structure.'));
        }

        $autorizaciones = $result->autorizaciones ?? null;
        $autorizacion = $autorizaciones->autorizacion ?? null;

        if ($autorizacion === null) {
            // No authorization record yet — likely still being processed
            return new SriAuthorizationResult(
                estado: 'EN PROCESO',
                numeroAutorizacion: null,
                fechaAutorizacion: null,
                ambiente: null,
                comprobante: null,
                mensajes: [],
                requestXml: $requestXml,
                responseXml: $responseXml,
                endpoint: $endpoint,
            );
        }

        if (! is_array($autorizacion)) {
            $autorizacion = [$autorizacion];
        }

        // Take the first (most recent) authorization record
        $auth = $autorizacion[0];
        $estado = (string) ($auth->estado ?? 'EN PROCESO');
        $mensajes = [];

        $mensajesObj = $auth->mensajes ?? null;

        if ($mensajesObj !== null) {
            $mensajeList = $mensajesObj->mensaje ?? [];

            if (! is_array($mensajeList)) {
                $mensajeList = [$mensajeList];
            }

            foreach ($mensajeList as $m) {
                $message = sprintf(
                    '[%s] %s: %s',
                    $m->tipo ?? 'INFO',
                    $m->identificador ?? '',
                    $m->mensaje ?? '',
                );

                $additionalInfo = preg_replace('/\s+/', ' ', mb_trim((string) ($m->informacionAdicional ?? '')));

                if (is_string($additionalInfo) && $additionalInfo !== '') {
                    $message .= sprintf(' | Detalle: %s', $additionalInfo);
                }

                $mensajes[] = $message;
            }
        }

        return new SriAuthorizationResult(
            estado: $estado,
            numeroAutorizacion: isset($auth->numeroAutorizacion) ? (string) $auth->numeroAutorizacion : null,
            fechaAutorizacion: isset($auth->fechaAutorizacion) ? (string) $auth->fechaAutorizacion : null,
            ambiente: isset($auth->ambiente) ? (string) $auth->ambiente : null,
            comprobante: isset($auth->comprobante) ? (string) $auth->comprobante : null,
            mensajes: $mensajes,
            requestXml: $requestXml,
            responseXml: $responseXml,
            endpoint: $endpoint,
        );
    }
}
