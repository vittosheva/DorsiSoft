<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use DOMDocument;
use Illuminate\Support\Facades\Log;
use LibXMLError;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Exceptions\XsdValidationException;

/**
 * Validates generated XML against the official SRI XSD schemas.
 */
final class XmlValidatorService
{
    private string $xsdBasePath;

    public function __construct()
    {
        $this->xsdBasePath = config('sri.electronic.xsd_path');
    }

    /**
     * @throws XsdValidationException If the XML does not conform to the XSD schema
     * @throws XmlGenerationException If the XSD file is not found
     */
    public function validate(string $xml, string $documentTypeCode, string $version): void
    {
        $xsdPath = $this->resolveXsdPath($documentTypeCode, $version);

        if (! file_exists($xsdPath)) {
            throw new XmlGenerationException("XSD schema file not found: {$xsdPath}");
        }

        $dom = new DOMDocument();

        $previousErrorReporting = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $loaded = $dom->loadXML($xml, LIBXML_NONET);

        if (! $loaded) {
            $errors = $this->collectLibXmlErrors();
            libxml_use_internal_errors($previousErrorReporting);
            Log::error(__('XML parsing errors'), ['errors' => $errors, 'xml' => $xml]);
            throw new XsdValidationException($errors, __('XML is not well-formed.'));
        }

        $valid = $dom->schemaValidate($xsdPath);

        $errors = $this->collectLibXmlErrors($xml);
        libxml_use_internal_errors($previousErrorReporting);

        if (! $valid) {
            Log::error(__('XSD validation errors'), ['errors' => $errors, 'xsd' => $xsdPath]);
            throw new XsdValidationException($errors, __('XML does not conform to SRI XSD schema.'));
        }
    }

    /**
     * Resolves the XSD file path for the given document type and version.
     */
    private function resolveXsdPath(string $documentTypeCode, string $version): string
    {
        $filename = match ($documentTypeCode) {
            '01' => "factura_V{$version}.xsd",
            '03' => "liquidacionCompra_V{$version}.xsd",
            '04' => "notaCredito_V{$version}.xsd",
            '05' => "notaDebito_V{$version}.xsd",
            '06' => "guiaRemision_V{$version}.xsd",
            '07' => "comprobanteRetencion_V{$version}.xsd",
            default => throw new XmlGenerationException("Unknown document type code: '{$documentTypeCode}'"),
        };

        return "{$this->xsdBasePath}/{$filename}";
    }

    /**
     * @return list<string>
     */
    private function collectLibXmlErrors(string $xml = ''): array
    {
        return array_map(
            fn (LibXMLError $error) => sprintf(
                '[%s] Line %d, Column %d: %s%s',
                match ($error->level) {
                    LIBXML_ERR_WARNING => 'WARNING',
                    LIBXML_ERR_ERROR => 'ERROR',
                    LIBXML_ERR_FATAL => 'FATAL',
                    default => 'UNKNOWN',
                },
                $error->line,
                $error->column,
                mb_trim($error->message),
                $xml !== '' ? "\n".$this->extractXmlContext($xml, $error->line) : '',
            ),
            libxml_get_errors(),
        );
    }

    /**
     * Extracts a snippet of XML lines around a given line number for error context.
     */
    private function extractXmlContext(string $xml, int $lineNumber, int $contextLines = 2): string
    {
        $lines = explode("\n", $xml);
        $start = max(0, $lineNumber - 1 - $contextLines);
        $end = min(count($lines) - 1, $lineNumber - 1 + $contextLines);

        $snippet = [];
        for ($i = $start; $i <= $end; $i++) {
            $marker = ($i === $lineNumber - 1) ? '>>>' : '   ';
            $snippet[] = sprintf('%s %4d: %s', $marker, $i + 1, $lines[$i]);
        }

        return implode("\n", $snippet);
    }
}
