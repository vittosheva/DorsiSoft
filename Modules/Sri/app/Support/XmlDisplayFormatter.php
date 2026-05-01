<?php

declare(strict_types=1);

namespace Modules\Sri\Support;

use DOMDocument;

final class XmlDisplayFormatter
{
    public function format(string $xml): string
    {
        $xml = mb_trim($xml);

        if ($xml === '') {
            return '';
        }

        $previousValue = libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $loaded = $dom->loadXML($xml, LIBXML_NOBLANKS);

        libxml_clear_errors();
        libxml_use_internal_errors($previousValue);

        if (! $loaded) {
            return $xml;
        }

        $formattedXml = $dom->saveXML();

        return $formattedXml === false ? $xml : $formattedXml;
    }
}
