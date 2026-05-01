<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml;

use Modules\Sri\Contracts\XmlGeneratorContract;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Services\Xml\Generators\CreditNoteXmlGenerator;
use Modules\Sri\Services\Xml\Generators\DebitNoteXmlGenerator;
use Modules\Sri\Services\Xml\Generators\DeliveryGuideXmlGenerator;
use Modules\Sri\Services\Xml\Generators\InvoiceXmlGenerator;
use Modules\Sri\Services\Xml\Generators\PurchaseSettlementXmlGenerator;
use Modules\Sri\Services\Xml\Generators\WithholdingXmlGenerator;

final class XmlGeneratorFactory
{
    /** @throws XmlGenerationException */
    public function make(string $documentTypeCode): XmlGeneratorContract
    {
        return match ($documentTypeCode) {
            '01' => app(InvoiceXmlGenerator::class),
            '03' => app(PurchaseSettlementXmlGenerator::class),
            '04' => app(CreditNoteXmlGenerator::class),
            '05' => app(DebitNoteXmlGenerator::class),
            '06' => app(DeliveryGuideXmlGenerator::class),
            '07' => app(WithholdingXmlGenerator::class),
            default => throw new XmlGenerationException("Unsupported SRI document type code: '{$documentTypeCode}'"),
        };
    }
}
