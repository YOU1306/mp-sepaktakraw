<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\RegistrationApplication;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FederationRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    private function district(): District
    {
        return District::create(['name' => 'Bhopal', 'code' => 'BPL']);
    }

    private function bearers(int $count = 7, bool $withSecretary = true): array
    {
        $bearers = [];
        for ($i = 0; $i < $count; $i++) {
            $bearers[] = [
                'name' => "Bearer $i",
                'contact' => '9876543210',
                'address' => 'Some address, Bhopal',
                'phone' => '0755123456',
                'email' => "bearer$i@example.com",
                'designation' => ($withSecretary && $i === 0) ? 'secretary' : 'member',
                'aadhaar' => UploadedFile::fake()->create("aadhaar$i.pdf", 80, 'application/pdf'),
            ];
        }

        return $bearers;
    }

    public function test_federation_registration_persists_application_pending_payment(): void
    {
        Storage::fake('local');
        $district = $this->district();

        $response = $this->post(route('register.federation.store'), [
            'registration_number' => 'FED-REG-001',
            'district_id' => $district->id,
            'billing_period' => 'quarterly',
            'acknowledgement' => UploadedFile::fake()->create('ack.pdf', 100, 'application/pdf'),
            'office_bearers' => $this->bearers(7),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('registration_applications', [
            'type' => 'federation',
            'status' => 'pending_payment',
            'applicant_email' => 'bearer0@example.com',
            'billing_period' => 'quarterly',
        ]);
        $this->assertDatabaseHas('federations', ['registration_number' => 'FED-REG-001']);

        $application = RegistrationApplication::first();
        $this->assertCount(7, $application->officeBearers);
        $this->assertNotNull($application->secretaryBearer);
        $this->assertCount(1, $application->documents); // acknowledgement
        $this->assertSame(500, $application->payment->amount);
    }

    public function test_federation_requires_a_secretary(): void
    {
        Storage::fake('local');
        $district = $this->district();

        $this->post(route('register.federation.store'), [
            'registration_number' => 'FED-REG-002',
            'district_id' => $district->id,
            'billing_period' => 'quarterly',
            'acknowledgement' => UploadedFile::fake()->create('ack.pdf', 100, 'application/pdf'),
            'office_bearers' => $this->bearers(7, withSecretary: false),
        ])->assertSessionHasErrors('office_bearers');
    }

    public function test_federation_requires_minimum_seven_bearers(): void
    {
        Storage::fake('local');
        $district = $this->district();

        $this->post(route('register.federation.store'), [
            'registration_number' => 'FED-REG-003',
            'district_id' => $district->id,
            'billing_period' => 'quarterly',
            'acknowledgement' => UploadedFile::fake()->create('ack.pdf', 100, 'application/pdf'),
            'office_bearers' => $this->bearers(5),
        ])->assertSessionHasErrors('office_bearers');
    }
}
