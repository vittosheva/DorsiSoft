<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Soap;

use Modules\Sri\Contracts\SriReceptionServiceContract;
use Modules\Sri\DTOs\SriReceptionResult;
use Modules\Sri\Enums\SriEnvironmentEnum;
use Modules\Sri\Exceptions\SriReceptionException;
use SoapClient;
use SoapFault;
use Throwable;

final class SriReceptionService implements SriReceptionServiceContract
{
    /** @var array<string, string> */
    private array $wsdlUrls = [
        'pruebas' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
        'produccion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
    ];

    /** @throws SriReceptionException */
    public function send(string $signedXml, SriEnvironmentEnum $env): SriReceptionResult
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

            $response = $client->validarComprobante(['xml' => $signedXml]);

            return $this->parseResponse(
                response: $response,
                requestXml: $client->__getLastRequest(),
                responseXml: $client->__getLastResponse(),
                endpoint: $wsdl,
            );
        } catch (SoapFault $e) {
            throw new SriReceptionException(
                __('SRI Reception SOAP fault: :message', ['message' => $e->getMessage()]),
                previous: $e,
            );
        } catch (Throwable $e) {
            throw new SriReceptionException(
                __('SRI Reception failed: :message', ['message' => $e->getMessage()]),
                previous: $e,
            );
        }
    }

    /** @throws SriReceptionException */
    private function parseResponse(
        mixed $response,
        ?string $requestXml = null,
        ?string $responseXml = null,
        ?string $endpoint = null,
    ): SriReceptionResult {
        $result = $response->RespuestaRecepcionComprobante ?? null;

        if ($result === null) {
            throw new SriReceptionException(__('Unexpected SRI reception response structure.'));
        }

        $estado = $result->estado ?? 'DEVUELTA';
        $mensajes = [];

        $comprobantes = $result->comprobantes ?? null;

        if ($comprobantes !== null) {
            $comprobante = $comprobantes->comprobante ?? null;

            if ($comprobante !== null) {
                $mensajesObj = $comprobante->mensajes ?? null;

                if ($mensajesObj !== null) {
                    $mensajeList = $mensajesObj->mensaje ?? [];

                    if (! is_array($mensajeList)) {
                        $mensajeList = [$mensajeList];
                    }

                    foreach ($mensajeList as $m) {
                        $mensajes[] = sprintf(
                            '[%s] %s: %s',
                            $m->tipo ?? 'ERROR',
                            $m->identificador ?? '',
                            $m->mensaje ?? '',
                        );
                    }
                }
            }
        }

        return new SriReceptionResult(
            estado: (string) $estado,
            mensajes: $mensajes,
            requestXml: $requestXml,
            responseXml: $responseXml,
            endpoint: $endpoint,
        );
    }
}
