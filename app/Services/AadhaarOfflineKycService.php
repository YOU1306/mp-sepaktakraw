<?php

namespace App\Services;

use DOMDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Throwable;
use ZipArchive;

/**
 * Verifies UIDAI's Offline e-KYC (the ZIP/XML a resident downloads from
 * myaadhaar.uidai.gov.in) without any OTP — matches the applicant's chosen
 * "offline paperless KYC" flow. The XML is digitally signed by UIDAI; we
 * always verify against our own pinned copy of UIDAI's certificate rather
 * than the certificate embedded in the file, so a tampered file can't
 * forge its own trust anchor.
 */
class AadhaarOfflineKycService
{
    public static function isConfigured(): bool
    {
        $path = self::certificatePath();

        return $path && file_exists($path);
    }

    public static function certificatePath(): ?string
    {
        return config('services.aadhaar.certificate_path');
    }

    /**
     * @param  array{name?: string, dob?: string}|null  $identity  The name/DOB the applicant typed into the
     *                                                             form, cross-checked against the Aadhaar file
     *                                                             to catch someone uploading a different
     *                                                             person's e-KYC file.
     * @return array{verified: bool, data: array<string, mixed>, note: string, identity_match: bool|null}
     */
    public static function verify(UploadedFile $file, ?string $shareCode = null, ?array $identity = null): array
    {
        $xml = self::extractXml($file, $shareCode);

        if ($xml === null) {
            return [
                'verified' => false,
                'data' => [],
                'note' => 'Could not read the uploaded file. Upload the "Offline e-KYC" ZIP or XML downloaded from myaadhaar.uidai.gov.in, with the matching share code.',
                'identity_match' => null,
            ];
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();

        if (! $loaded) {
            return [
                'verified' => false,
                'data' => [],
                'note' => 'The uploaded file is not a valid Offline e-KYC XML.',
                'identity_match' => null,
            ];
        }

        $data = self::extractDemographics($dom);
        $identityMatch = $identity ? self::matchesIdentity($data, $identity) : null;
        $mismatchWarning = $identityMatch === false
            ? ' The name and/or date of birth on the Aadhaar file do not match what was entered on the form — manual review required.'
            : '';

        if (! self::isConfigured()) {
            return [
                'verified' => false,
                'data' => $data,
                'note' => 'UIDAI certificate not configured on this server — data extracted but not cryptographically verified. Manual verification required by the reviewer.'.$mismatchWarning,
                'identity_match' => $identityMatch,
            ];
        }

        $verified = self::verifySignature($dom);

        return [
            'verified' => $verified,
            'data' => $data,
            'note' => ($verified
                ? 'Digital signature verified against UIDAI certificate.'
                : 'Digital signature verification FAILED — the file may be corrupted, edited, or not genuinely from UIDAI. Manual review required.').$mismatchWarning,
            'identity_match' => $identityMatch,
        ];
    }

    /**
     * Only checks name + date of birth, per the applicant-facing document upload flow — this is not a
     * full biometric/photo match, just a plausibility check that the uploaded e-KYC file belongs to the
     * person filling in the form.
     *
     * @param  array<string, mixed>  $extracted
     * @param  array{name?: string, dob?: string}  $identity
     */
    protected static function matchesIdentity(array $extracted, array $identity): bool
    {
        $nameMatch = self::namesMatch((string) ($extracted['name'] ?? ''), (string) ($identity['name'] ?? ''));
        $dobMatch = self::datesMatch($extracted['dob'] ?? null, $identity['dob'] ?? null);

        return $nameMatch && $dobMatch;
    }

    protected static function namesMatch(string $a, string $b): bool
    {
        $normalize = fn (string $s) => trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Z ]/', '', strtoupper($s)) ?? ''));

        $na = $normalize($a);
        $nb = $normalize($b);

        if ($na === '' || $nb === '') {
            return false;
        }

        if ($na === $nb) {
            return true;
        }

        similar_text($na, $nb, $percent);

        return $percent >= 80.0;
    }

    protected static function datesMatch(?string $extractedDob, ?string $formDob): bool
    {
        $a = self::parseDob($extractedDob);
        $b = self::parseDob($formDob);

        return $a !== null && $b !== null && $a->isSameDay($b);
    }

    protected static function parseDob(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected static function extractXml(UploadedFile $file, ?string $shareCode): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'xml') {
            return file_get_contents($file->getRealPath()) ?: null;
        }

        if ($ext === 'zip') {
            $zip = new ZipArchive;
            if ($zip->open($file->getRealPath()) !== true) {
                return null;
            }

            if ($shareCode) {
                $zip->setPassword($shareCode);
            }

            $xmlContents = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_ends_with(strtolower($name), '.xml')) {
                    $xmlContents = $zip->getFromIndex($i) ?: null;
                    break;
                }
            }
            $zip->close();

            return $xmlContents;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function extractDemographics(DOMDocument $dom): array
    {
        $data = [];

        $root = $dom->documentElement;
        if ($root) {
            $data['reference_id'] = $root->getAttribute('referenceId') ?: null;
        }

        $poiNodes = $dom->getElementsByTagName('Poi');
        if ($poiNodes->length > 0) {
            $poi = $poiNodes->item(0);
            $data['name'] = $poi->getAttribute('name') ?: null;
            $data['dob'] = $poi->getAttribute('dob') ?: null;
            $data['gender'] = $poi->getAttribute('gender') ?: null;
        }

        $poaNodes = $dom->getElementsByTagName('Poa');
        if ($poaNodes->length > 0) {
            $poa = $poaNodes->item(0);
            $addressParts = array_filter([
                $poa->getAttribute('house'),
                $poa->getAttribute('street'),
                $poa->getAttribute('lm'),
                $poa->getAttribute('loc'),
                $poa->getAttribute('vtc'),
                $poa->getAttribute('subdist'),
                $poa->getAttribute('dist'),
                $poa->getAttribute('state'),
            ]);
            $data['address'] = implode(', ', $addressParts) ?: null;
            $data['pincode'] = $poa->getAttribute('pc') ?: null;
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }

    protected static function verifySignature(DOMDocument $dom): bool
    {
        try {
            $objDSig = new XMLSecurityDSig;
            $signatureNode = $objDSig->locateSignature($dom);

            if (! $signatureNode) {
                return false;
            }

            $objDSig->canonicalizeSignedInfo();

            if (! $objDSig->validateReference()) {
                return false;
            }

            $algoNode = $dom->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'SignatureMethod');
            $algorithm = $algoNode->length ? $algoNode->item(0)->getAttribute('Algorithm') : '';
            $keyType = str_contains($algorithm, 'sha256') ? XMLSecurityKey::RSA_SHA256 : XMLSecurityKey::RSA_SHA1;

            $objKey = new XMLSecurityKey($keyType, ['type' => 'public']);
            $objKey->loadKey(self::certificatePath(), true, true);

            return (bool) $objDSig->verify($objKey);
        } catch (Throwable) {
            return false;
        }
    }
}
