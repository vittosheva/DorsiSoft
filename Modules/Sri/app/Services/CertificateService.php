<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Company;
use Modules\Sri\Exceptions\XmlSigningException;
use OpenSSLAsymmetricKey;
use Throwable;

/**
 * Gestiona el certificado digital (.p12) de firma electrónica por empresa.
 */
final class CertificateService
{
    /**
     * Carga el certificado p12 de la empresa y retorna sus componentes.
     *
     * @return array{privateKey: OpenSSLAsymmetricKey, certificate: string, chain: list<string>}
     *
     * @throws XmlSigningException Si no hay certificado configurado o es inválido
     */
    public function loadCertificate(Company $company): array
    {
        if (blank($company->certificate_path) || blank($company->certificate_password_encrypted)) {
            throw new XmlSigningException(__('Company [:ruc] does not have a digital certificate configured.', ['ruc' => $company->ruc]));
        }

        $p12Content = Storage::disk('local')->get($company->certificate_path);

        if ($p12Content === null) {
            throw new XmlSigningException(__('Certificate file not found at path [:path].', ['path' => $company->certificate_path]));
        }

        $password = $this->decryptPassword($company->certificate_password_encrypted);

        $parsed = $this->parsePkcs12($p12Content, $password);

        $privateKey = openssl_pkey_get_private($parsed['pkey']);

        if ($privateKey === false) {
            throw new XmlSigningException(__('Failed to extract private key from certificate.'));
        }

        return [
            'privateKey' => $privateKey,
            'certificate' => $parsed['cert'],
            'chain' => array_values($parsed['extracerts'] ?? []),
        ];
    }

    /**
     * Cifra la contraseña del certificado para almacenamiento seguro.
     */
    public function encryptPassword(string $password): string
    {
        return Crypt::encryptString($password);
    }

    /**
     * Descifra la contraseña del certificado para su uso.
     *
     * @throws XmlSigningException Si el descifrado falla
     */
    public function decryptPassword(string $encryptedPassword): string
    {
        try {
            return Crypt::decryptString($encryptedPassword);
        } catch (Throwable $e) {
            throw new XmlSigningException(__('Failed to decrypt certificate password.'), previous: $e);
        }
    }

    /**
     * Valida que el certificado p12 puede abrirse con la contraseña proporcionada.
     *
     * @throws XmlSigningException Si el archivo no existe o la contraseña es incorrecta
     */
    public function validateCertificatePassword(string $certificatePath, string $rawPassword): void
    {
        $content = Storage::disk('local')->get($certificatePath);

        if ($content === null) {
            throw new XmlSigningException(__('Certificate file not found at path [:path].', ['path' => $certificatePath]));
        }

        $this->parsePkcs12($content, $rawPassword);
    }

    /**
     * Extrae el issuer DN y el número de serie del certificado para XAdES IssuerSerial.
     *
     * @return array{issuerName: string, serialNumber: string}
     *
     * @throws XmlSigningException Si el certificado no puede ser leído
     */
    public function extractCertificateInfo(string $pemCertificate): array
    {
        $cert = openssl_x509_read($pemCertificate);

        if ($cert === false) {
            throw new XmlSigningException(__('Failed to read X.509 certificate for info extraction.'));
        }

        $info = openssl_x509_parse($cert, false);

        if ($info === false) {
            throw new XmlSigningException(__('Failed to parse X.509 certificate.'));
        }

        /** @var array<string, string> $issuerArray */
        $issuerArray = $info['issuer'] ?? [];

        $attributeMap = [
            'countryName' => 'C',
            'organizationName' => 'O',
            'organizationalUnitName' => 'OU',
            'commonName' => 'CN',
            'serialNumber' => 'SERIALNUMBER',
            'emailAddress' => 'E',
        ];

        $issuerParts = [];

        foreach (array_reverse($issuerArray) as $key => $value) {
            $issuerParts[] = ($attributeMap[$key] ?? $key).'='.$value;
        }

        $serialNumber = isset($info['serialNumberHex'])
            ? (mb_ltrim((string) base_convert((string) $info['serialNumberHex'], 16, 10), '0') ?: '0')
            : (string) ($info['serialNumber'] ?? '0');

        return [
            'issuerName' => implode(',', $issuerParts),
            'serialNumber' => $serialNumber,
        ];
    }

    /**
     * Extrae el contenido DER del certificado (Base64 sin cabeceras PEM).
     */
    public function extractCertificateDer(string $pemCertificate): string
    {
        $lines = explode("\n", mb_trim($pemCertificate));

        $der = '';
        $capture = false;

        foreach ($lines as $line) {
            if (str_starts_with($line, '-----BEGIN CERTIFICATE-----')) {
                $capture = true;

                continue;
            }

            if (str_starts_with($line, '-----END CERTIFICATE-----')) {
                break;
            }

            if ($capture) {
                $der .= mb_trim($line);
            }
        }

        return $der;
    }

    /**
     * Lee el PKCS#12 con OpenSSL nativo. Si el archivo usa algoritmos legacy
     * (frecuente en certificados SRI del Ecuador), usa el CLI de openssl con -legacy.
     *
     * @return array{pkey: string, cert: string, extracerts: list<string>}
     *
     * @throws XmlSigningException
     */
    private function parsePkcs12(string $content, string $password): array
    {
        $certs = [];

        if (openssl_pkcs12_read($content, $certs, $password)) {
            return $certs;
        }

        // Drain the error queue before the CLI fallback
        while (openssl_error_string()) {
        }

        // Always attempt the legacy CLI fallback. If the password is wrong or the
        // file is corrupt, the CLI will also fail and throw the appropriate exception.
        return $this->parseLegacyPkcs12($content, $password);
    }

    /**
     * Extrae el contenido de un PKCS#12 con algoritmos legacy usando el CLI de openssl.
     * Necesario cuando OpenSSL 3.x no tiene el provider legacy disponible en PHP.
     *
     * @return array{pkey: string, cert: string, extracerts: list<string>}
     *
     * @throws XmlSigningException
     */
    private function parseLegacyPkcs12(string $content, string $password): array
    {
        $p12Tmp = tempnam(sys_get_temp_dir(), 'p12_');
        $passTmp = tempnam(sys_get_temp_dir(), 'p12pass_');

        try {
            file_put_contents($p12Tmp, $content, LOCK_EX);
            chmod($p12Tmp, 0600);
            file_put_contents($passTmp, $password, LOCK_EX);
            chmod($passTmp, 0600);

            $opensslBin = (string) config('sri.openssl_binary', '/usr/local/bin/openssl');

            $process = proc_open(
                [$opensslBin, 'pkcs12', '-legacy', '-in', $p12Tmp, '-passin', 'file:'.$passTmp, '-noenc'],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );

            if ($process === false) {
                throw new XmlSigningException(__('Failed to read certificate. openssl CLI is not available.'));
            }

            fclose($pipes[0]);
            $pem = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            if ($exitCode !== 0 || blank($pem)) {
                throw new XmlSigningException(__('Failed to read certificate. The password may be incorrect or the file may be corrupt.'));
            }

            preg_match('/-----BEGIN (?:\w+ )*PRIVATE KEY-----.*?-----END (?:\w+ )*PRIVATE KEY-----/s', $pem, $keyMatch);
            preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $pem, $certMatches);

            $certBlocks = $certMatches[0] ?? [];

            if (empty($keyMatch[0]) || empty($certBlocks)) {
                throw new XmlSigningException(__('Failed to extract private key or certificate from p12 file.'));
            }

            return [
                'pkey' => $keyMatch[0],
                'cert' => $certBlocks[0],
                'extracerts' => array_slice($certBlocks, 1),
            ];
        } finally {
            @unlink($p12Tmp);
            @unlink($passTmp);
        }
    }
}
