<?php

namespace Tests\Feature;

use App\Services\AadhaarOfflineKycService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AadhaarOfflineKycServiceTest extends TestCase
{
    private function fakeAadhaarXml(string $name, string $dob): UploadedFile
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<OfflinePaperlessKyc referenceId="123456789012345678">
    <UidData>
        <Poi name="{$name}" dob="{$dob}" gender="M"/>
        <Poa vtc="Bhopal" dist="Bhopal" state="Madhya Pradesh" pc="462001"/>
    </UidData>
</OfflinePaperlessKyc>
XML;

        $path = tempnam(sys_get_temp_dir(), 'aadhaar').'.xml';
        file_put_contents($path, $xml);

        return new UploadedFile($path, 'aadhaar.xml', 'text/xml', null, true);
    }

    public function test_identity_match_is_null_when_no_identity_is_supplied(): void
    {
        $result = AadhaarOfflineKycService::verify($this->fakeAadhaarXml('Test Player', '01-01-2010'));

        $this->assertNull($result['identity_match']);
        $this->assertSame('Test Player', $result['data']['name']);
    }

    public function test_identity_match_is_true_when_name_and_dob_match_the_form(): void
    {
        $result = AadhaarOfflineKycService::verify(
            $this->fakeAadhaarXml('Test Player', '01-01-2010'),
            null,
            ['name' => 'Test Player', 'dob' => '2010-01-01']
        );

        $this->assertTrue($result['identity_match']);
        $this->assertStringNotContainsString('do not match', $result['note']);
    }

    public function test_identity_match_tolerates_minor_case_and_spacing_differences(): void
    {
        $result = AadhaarOfflineKycService::verify(
            $this->fakeAadhaarXml('TEST   PLAYER', '01-01-2010'),
            null,
            ['name' => 'test player', 'dob' => '2010-01-01']
        );

        $this->assertTrue($result['identity_match']);
    }

    public function test_identity_match_is_false_when_name_differs(): void
    {
        $result = AadhaarOfflineKycService::verify(
            $this->fakeAadhaarXml('Someone Else Entirely', '01-01-2010'),
            null,
            ['name' => 'Test Player', 'dob' => '2010-01-01']
        );

        $this->assertFalse($result['identity_match']);
        $this->assertStringContainsString('do not match', $result['note']);
    }

    public function test_identity_match_is_false_when_dob_differs(): void
    {
        $result = AadhaarOfflineKycService::verify(
            $this->fakeAadhaarXml('Test Player', '01-01-2010'),
            null,
            ['name' => 'Test Player', 'dob' => '1995-06-15']
        );

        $this->assertFalse($result['identity_match']);
    }
}
