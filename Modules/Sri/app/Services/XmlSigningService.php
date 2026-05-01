<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Modules\Core\Models\Company;
use Modules\Sri\Exceptions\XmlSigningException;
use OpenSSLAsymmetricKey;

/**
 * Firma documentos XML con el estándar XAdES-BES requerido por el SRI Ecuador.
 *
 * Algoritmos:
 *   - Digest:    SHA-1 (requisito SRI)
 *   - Signature: RSA-SHA1
 *   - Canonicalización: Canonical XML 1.0
 */
final class XmlSigningService
{
    public function __construct(private readonly CertificateService $certificateService) {}

    /**
     * @throws XmlSigningException
     */
    public function sign(string $xml, Company $company): string
    {
        ['privateKey' => $privateKey, 'certificate' => $certificatePem] = $this->certificateService->loadCertificate($company);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        if (! $dom->loadXML($xml)) {
            throw new XmlSigningException(__('Could not parse XML before signing.'));
        }

        $certDer = $this->certificateService->extractCertificateDer($certificatePem);
        $certDigest = base64_encode(hash('sha1', base64_decode($certDer), binary: true));
        $certInfo = $this->certificateService->extractCertificateInfo($certificatePem);

        $signatureId = 'Signature'.random_int(100000, 999999);
        $signedPropsId = $signatureId.'-SignedProperties'.random_int(100000, 999999);
        $keyInfoId = 'Certificate'.random_int(1000000, 9999999);
        $signedInfoId = $signatureId.'-SignedInfo'.random_int(100000, 999999);
        $objectId = $signatureId.'-Object'.random_int(100000, 999999);
        $referenceId = 'Reference-ID-'.random_int(100000, 999999);
        $signingTime = now()->toIso8601String();

        $comprobante = $dom->documentElement;

        if ($comprobante === null) {
            throw new XmlSigningException(__('XML has no root element to sign.'));
        }

        $c14nComprobante = $comprobante->C14N();

        if ($c14nComprobante === false) {
            throw new XmlSigningException('Failed to canonicalize XML root element.');
        }

        $comprobanteDigest = base64_encode(hash('sha1', $c14nComprobante, binary: true));

        $signedProperties = $this->buildSignedPropertiesElement(
            $dom,
            $signedPropsId,
            $signingTime,
            $certDigest,
            $certInfo['issuerName'],
            $certInfo['serialNumber'],
            $referenceId
        );

        $keyInfo = $this->buildKeyInfoElement($dom, $keyInfoId, $certDer, $privateKey);

        $signature = $this->buildSignatureSkeleton(
            $dom,
            $signatureId,
            $keyInfo,
            $signedProperties,
            $objectId
        );

        $dom->documentElement->appendChild($signature);

        $signedPropsDigest = $this->calculateNodeDigestById($dom, $signedPropsId);
        $keyInfoDigest = $this->calculateNodeDigestById($dom, $keyInfoId);

        $signedInfo = $this->buildSignedInfoElement(
            $dom,
            $comprobanteDigest,
            $signedPropsDigest,
            $keyInfoDigest,
            $signedPropsId,
            $keyInfoId,
            $referenceId,
            $signedInfoId
        );
        $signature->insertBefore($signedInfo, $signature->firstChild);

        $signedPropsDigest = $this->calculateNodeDigestById($dom, $signedPropsId);
        $keyInfoDigest = $this->calculateNodeDigestById($dom, $keyInfoId);

        $updatedSignedInfo = $this->buildSignedInfoElement(
            $dom,
            $comprobanteDigest,
            $signedPropsDigest,
            $keyInfoDigest,
            $signedPropsId,
            $keyInfoId,
            $referenceId,
            $signedInfoId
        );
        $signature->replaceChild($updatedSignedInfo, $signedInfo);
        $signedInfo = $updatedSignedInfo;

        $signedInfoC14n = $signedInfo->C14N();

        if ($signedInfoC14n === false || $signedInfoC14n === null) {
            throw new XmlSigningException('Failed to canonicalize SignedInfo element.');
        }

        $signatureValue = '';
        $signed = openssl_sign($signedInfoC14n, $signatureValue, $privateKey, OPENSSL_ALGO_SHA1);

        if (! $signed) {
            throw new XmlSigningException(__('Failed to sign the document: :error', ['error' => openssl_error_string()]));
        }

        $signatureValueB64 = base64_encode($signatureValue);

        $signatureValueElement = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:SignatureValue');
        $signatureValueElement->setAttribute('Id', 'SignatureValue'.random_int(1000000, 9999999));
        $signatureValueElement->appendChild($dom->createTextNode($signatureValueB64));

        $signature->insertBefore($signatureValueElement, $signature->childNodes->item(1) ?? null);

        $result = $dom->saveXML();

        if ($result === false) {
            throw new XmlSigningException(__('Failed to serialize signed XML.'));
        }

        return $result;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function buildSignedPropertiesElement(
        DOMDocument $dom,
        string $signedPropsId,
        string $signingTime,
        string $certDigest,
        string $issuerName,
        string $serialNumber,
        string $referenceId
    ): DOMElement {
        $nsDs = 'http://www.w3.org/2000/09/xmldsig#';
        $nsEtsi = 'http://uri.etsi.org/01903/v1.3.2#';

        $signedProperties = $dom->createElementNS($nsEtsi, 'etsi:SignedProperties');
        $signedProperties->setAttribute('Id', $signedPropsId);

        $signedSignatureProperties = $dom->createElementNS($nsEtsi, 'etsi:SignedSignatureProperties');
        $signedProperties->appendChild($signedSignatureProperties);

        $signingTimeElement = $dom->createElementNS($nsEtsi, 'etsi:SigningTime');
        $signingTimeElement->appendChild($dom->createTextNode($signingTime));
        $signedSignatureProperties->appendChild($signingTimeElement);

        $signingCertificate = $dom->createElementNS($nsEtsi, 'etsi:SigningCertificate');
        $signedSignatureProperties->appendChild($signingCertificate);

        $cert = $dom->createElementNS($nsEtsi, 'etsi:Cert');
        $signingCertificate->appendChild($cert);

        $certDigestElement = $dom->createElementNS($nsEtsi, 'etsi:CertDigest');
        $cert->appendChild($certDigestElement);

        $digestMethod = $dom->createElementNS($nsDs, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');
        $certDigestElement->appendChild($digestMethod);

        $digestValue = $dom->createElementNS($nsDs, 'ds:DigestValue');
        $digestValue->appendChild($dom->createTextNode($certDigest));
        $certDigestElement->appendChild($digestValue);

        $issuerSerial = $dom->createElementNS($nsEtsi, 'etsi:IssuerSerial');
        $cert->appendChild($issuerSerial);

        $issuerNameElement = $dom->createElementNS($nsDs, 'ds:X509IssuerName');
        $issuerNameElement->appendChild($dom->createTextNode($issuerName));
        $issuerSerial->appendChild($issuerNameElement);

        $serialNumberElement = $dom->createElementNS($nsDs, 'ds:X509SerialNumber');
        $serialNumberElement->appendChild($dom->createTextNode($serialNumber));
        $issuerSerial->appendChild($serialNumberElement);

        $signedDataObjectProperties = $dom->createElementNS($nsEtsi, 'etsi:SignedDataObjectProperties');
        $signedProperties->appendChild($signedDataObjectProperties);

        $dataObjectFormat = $dom->createElementNS($nsEtsi, 'etsi:DataObjectFormat');
        $dataObjectFormat->setAttribute('ObjectReference', "#{$referenceId}");
        $signedDataObjectProperties->appendChild($dataObjectFormat);

        $description = $dom->createElementNS($nsEtsi, 'etsi:Description');
        $description->appendChild($dom->createTextNode('contenido comprobante'));
        $dataObjectFormat->appendChild($description);

        $mimeType = $dom->createElementNS($nsEtsi, 'etsi:MimeType');
        $mimeType->appendChild($dom->createTextNode('text/xml'));
        $dataObjectFormat->appendChild($mimeType);

        return $signedProperties;
    }

    private function buildSignedInfoElement(
        DOMDocument $dom,
        string $comprobanteDigest,
        string $signedPropsDigest,
        string $keyInfoDigest,
        string $signedPropsId,
        string $keyInfoId,
        string $referenceId,
        string $signedInfoId
    ): DOMElement {
        $nsDs = 'http://www.w3.org/2000/09/xmldsig#';

        $signedInfo = $dom->createElementNS($nsDs, 'ds:SignedInfo');
        $signedInfo->setAttribute('Id', $signedInfoId);

        $canonicalizationMethod = $dom->createElementNS($nsDs, 'ds:CanonicalizationMethod');
        $canonicalizationMethod->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
        $signedInfo->appendChild($canonicalizationMethod);

        $signatureMethod = $dom->createElementNS($nsDs, 'ds:SignatureMethod');
        $signatureMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');
        $signedInfo->appendChild($signatureMethod);

        $signedPropertiesReference = $this->buildReferenceElement(
            $dom,
            id: 'SignedPropertiesID'.$signedPropsId,
            uri: '#'.$signedPropsId,
            digestValue: $signedPropsDigest,
            type: 'http://uri.etsi.org/01903#SignedProperties',
        );
        $signedInfo->appendChild($signedPropertiesReference);

        $keyInfoReference = $this->buildReferenceElement(
            $dom,
            id: null,
            uri: '#'.$keyInfoId,
            digestValue: $keyInfoDigest,
            type: null,
        );
        $signedInfo->appendChild($keyInfoReference);

        $comprobanteReference = $this->buildReferenceElement(
            $dom,
            id: $referenceId,
            uri: '#comprobante',
            digestValue: $comprobanteDigest,
            type: null,
            includeEnvelopedTransform: true,
        );
        $signedInfo->appendChild($comprobanteReference);

        return $signedInfo;
    }

    /**
     * @throws XmlSigningException
     */
    private function buildKeyInfoElement(DOMDocument $dom, string $keyInfoId, string $certDer, OpenSSLAsymmetricKey $privateKey): DOMElement
    {
        $details = openssl_pkey_get_details($privateKey);

        if ($details === false || ! isset($details['rsa']['n'], $details['rsa']['e'])) {
            throw new XmlSigningException('Failed to extract RSA public key details for XML signature.');
        }

        $modulus = base64_encode($details['rsa']['n']);
        $exponent = base64_encode($details['rsa']['e']);

        $nsDs = 'http://www.w3.org/2000/09/xmldsig#';

        $keyInfo = $dom->createElementNS($nsDs, 'ds:KeyInfo');
        $keyInfo->setAttribute('Id', $keyInfoId);

        $x509Data = $dom->createElementNS($nsDs, 'ds:X509Data');
        $keyInfo->appendChild($x509Data);

        $x509Certificate = $dom->createElementNS($nsDs, 'ds:X509Certificate');
        $x509Certificate->appendChild($dom->createTextNode($certDer));
        $x509Data->appendChild($x509Certificate);

        $keyValue = $dom->createElementNS($nsDs, 'ds:KeyValue');
        $keyInfo->appendChild($keyValue);

        $rsaKeyValue = $dom->createElementNS($nsDs, 'ds:RSAKeyValue');
        $keyValue->appendChild($rsaKeyValue);

        $modulusElement = $dom->createElementNS($nsDs, 'ds:Modulus');
        $modulusElement->appendChild($dom->createTextNode($modulus));
        $rsaKeyValue->appendChild($modulusElement);

        $exponentElement = $dom->createElementNS($nsDs, 'ds:Exponent');
        $exponentElement->appendChild($dom->createTextNode($exponent));
        $rsaKeyValue->appendChild($exponentElement);

        return $keyInfo;
    }

    private function buildSignatureSkeleton(
        DOMDocument $dom,
        string $signatureId,
        DOMElement $keyInfo,
        DOMElement $signedProperties,
        string $objectId
    ): DOMElement {
        $nsDs = 'http://www.w3.org/2000/09/xmldsig#';
        $nsEtsi = 'http://uri.etsi.org/01903/v1.3.2#';

        $signature = $dom->createElementNS($nsDs, 'ds:Signature');
        $signature->setAttribute('Id', $signatureId);

        // <ds:KeyInfo>
        $signature->appendChild($keyInfo);

        // <ds:Object> / QualifyingProperties / SignedProperties
        $object = $dom->createElementNS($nsDs, 'ds:Object');
        $object->setAttribute('Id', $objectId);
        $qualProps = $dom->createElementNS($nsEtsi, 'etsi:QualifyingProperties');
        $qualProps->setAttribute('Target', "#{$signatureId}");

        $qualProps->appendChild($signedProperties);

        $object->appendChild($qualProps);
        $signature->appendChild($object);

        return $signature;
    }

    /**
     * @throws XmlSigningException
     */
    private function calculateNodeDigestById(DOMDocument $dom, string $id): string
    {
        $xpath = new DOMXPath($dom);
        $node = $xpath->query("//*[@Id='{$id}' or @id='{$id}']")->item(0);

        if (! $node instanceof DOMElement) {
            throw new XmlSigningException("Failed to locate XML signature node [{$id}] for digest calculation.");
        }

        $c14n = $node->C14N();

        if ($c14n === false || $c14n === null) {
            throw new XmlSigningException("Failed to canonicalize XML signature node [{$id}].");
        }

        return base64_encode(hash('sha1', $c14n, binary: true));
    }

    private function buildReferenceElement(
        DOMDocument $dom,
        ?string $id,
        string $uri,
        string $digestValue,
        ?string $type,
        bool $includeEnvelopedTransform = false
    ): DOMElement {
        $nsDs = 'http://www.w3.org/2000/09/xmldsig#';

        $reference = $dom->createElementNS($nsDs, 'ds:Reference');

        if ($id !== null) {
            $reference->setAttribute('Id', $id);
        }

        if ($type !== null) {
            $reference->setAttribute('Type', $type);
        }

        $reference->setAttribute('URI', $uri);

        if ($includeEnvelopedTransform) {
            $transforms = $dom->createElementNS($nsDs, 'ds:Transforms');
            $reference->appendChild($transforms);

            $transform = $dom->createElementNS($nsDs, 'ds:Transform');
            $transform->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
            $transforms->appendChild($transform);
        }

        $digestMethod = $dom->createElementNS($nsDs, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');
        $reference->appendChild($digestMethod);

        $digestValueElement = $dom->createElementNS($nsDs, 'ds:DigestValue');
        $digestValueElement->appendChild($dom->createTextNode($digestValue));
        $reference->appendChild($digestValueElement);

        return $reference;
    }
}
