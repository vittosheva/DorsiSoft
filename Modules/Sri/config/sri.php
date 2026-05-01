<?php

declare(strict_types=1);

return [
    // ── SRI REST API (RUC lookup) ─────────────────────────────────────────────
    'base_url' => env('SRI_BASE_URL', 'https://srienlinea.sri.gob.ec'),
    'consultar_endpoint' => env('SRI_CONSULTAR_ENDPOINT', '/sri-catastro-sujeto-servicio-internet/rest/ConsolidadoContribuyente/obtenerPorNumerosRuc'),
    'query_param' => env('SRI_QUERY_PARAM', 'ruc'),
    'establecimientos_endpoint' => env('SRI_ESTABLECIMIENTOS_ENDPOINT', '/sri-catastro-sujeto-servicio-internet/rest/Establecimiento/consultarPorNumeroRuc'),
    'establecimientos_query_param' => env('SRI_ESTABLECIMIENTOS_QUERY_PARAM', 'numeroRuc'),
    'timeout' => (int) env('SRI_TIMEOUT', 8),
    'connect_timeout' => (int) env('SRI_CONNECT_TIMEOUT', 3),
    'cache_ttl' => (int) env('SRI_CACHE_TTL', 3600),

    // ── Certificate handling ─────────────────────────────────────────────────
    'openssl_binary' => env('OPENSSL_BINARY', '/usr/local/bin/openssl'),

    /*
    openssl list -providers
    # Si no aparece "legacy":
    sudo apt install -y openssl
    # En Debian/Ubuntu puede requerir:
    sudo apt install -y libssl3 openssl
    */

    // ── Electronic billing pipeline ──────────────────────────────────────────
    'electronic' => [
        'validate_xsd' => (bool) env('SRI_VALIDATE_XSD', true),
        'xsd_path' => base_path(env('SRI_XSD_PATH', 'Modules/Sri/xsd')),
        'xml_storage_disk' => env('SRI_XML_DISK', 'local'),
        'soap_timeout' => (int) env('SRI_SOAP_TIMEOUT', 30),
        'soap_connect_timeout' => (int) env('SRI_SOAP_CONNECT_TIMEOUT', 10),
        'poll_interval_minutes' => (int) env('SRI_POLL_INTERVAL_MINUTES', 5),

        'versions' => [
            '01' => '2.1.0', // Factura
            '03' => '1.1.0', // Liquidación de Compra
            '04' => '1.1.0', // Nota de Crédito
            '05' => '1.0.0', // Nota de Débito
            '06' => '1.1.0', // Guía de Remisión
            '07' => '2.0.0', // Comprobante de Retención
        ],

        'wsdl' => [
            'reception' => [
                'pruebas' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
                'produccion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
            ],
            'authorization' => [
                'pruebas' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
                'produccion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
            ],
        ],
    ],
];
