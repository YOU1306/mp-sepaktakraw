<?php

namespace Tests\Feature;

use App\Mail\RegistrationApprovedMail;
use App\Mail\RegistrationRejectedMail;
use App\Models\District;
use App\Models\RegistrationApplication;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\OtpService;
use App\Services\RegistrationReviewService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IndividualRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    private function verifiedToken(string $channel, string $destination): string
    {
        $result = OtpService::send($channel, $destination);
        $code = VerificationCode::where('token', $result['token'])->value('code');
        OtpService::verify($result['token'], $code);

        return $result['token'];
    }

    private function validPayload(array $overrides = []): array
    {
        $district = District::create(['name' => 'Bhopal', 'code' => 'BPL']);

        return array_merge([
            'member_role' => 'player',
            'category' => 'junior',
            'name' => 'Test Player',
            'father_name' => 'Father Name',
            'mother_name' => 'Mother Name',
            'dob' => '2010-01-01',
            'sex' => 'male',
            'email' => 'player@example.com',
            'address' => '123 Test Street, Bhopal',
            'contact_number' => '9876543210',
            'district_id' => $district->id,
            'billing_period' => 'quarterly',
            'aadhaar' => UploadedFile::fake()->create('aadhaar.xml', 10, 'text/xml'),
            'marksheet' => UploadedFile::fake()->create('marksheet.pdf', 100, 'application/pdf'),
            'photo' => UploadedFile::fake()->image('photo.jpg'),
            'birth_certificate' => UploadedFile::fake()->create('birth.pdf', 100, 'application/pdf'),
            'phone_token' => $this->verifiedToken(VerificationCode::CHANNEL_PHONE, '9876543210'),
            'email_token' => $this->verifiedToken(VerificationCode::CHANNEL_EMAIL, 'player@example.com'),
        ], $overrides);
    }

    public function test_individual_registration_creates_application_pending_payment(): void
    {
        Storage::fake('local');

        $response = $this->post(route('register.individual.store'), $this->validPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('registration_applications', [
            'type' => 'individual',
            'status' => 'pending_payment',
            'applicant_email' => 'player@example.com',
            'billing_period' => 'quarterly',
        ]);
        $this->assertDatabaseHas('players', ['name' => 'Test Player', 'category' => 'junior', 'member_role' => 'player']);

        $application = RegistrationApplication::first();
        $this->assertCount(4, $application->player->documents); // photo, aadhaar, marksheet, birth certificate
        $this->assertNotNull($application->payment);
        $this->assertSame(100, $application->payment->amount);
    }

    public function test_official_registration_does_not_require_category_or_player_documents(): void
    {
        Storage::fake('local');

        $payload = $this->validPayload([
            'member_role' => 'coach',
            'category' => null,
        ]);
        unset($payload['marksheet'], $payload['birth_certificate']);

        $response = $this->post(route('register.individual.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('players', ['name' => 'Test Player', 'member_role' => 'coach', 'category' => null]);
    }

    public function test_invalid_contact_number_is_rejected(): void
    {
        Storage::fake('local');

        $payload = $this->validPayload();
        $payload['contact_number'] = '12345';

        $this->post(route('register.individual.store'), $payload)
            ->assertSessionHasErrors('contact_number');
    }

    public function test_registration_requires_verified_phone_and_email(): void
    {
        Storage::fake('local');

        $payload = $this->validPayload();
        $payload['phone_token'] = 'not-a-real-token';

        $this->post(route('register.individual.store'), $payload)
            ->assertSessionHasErrors('phone_token');
    }

    public function test_approval_after_payment_creates_account_with_membership_expiry_and_sends_credentials(): void
    {
        Storage::fake('local');
        Mail::fake();

        $this->post(route('register.individual.store'), $this->validPayload());
        $application = RegistrationApplication::first();
        $application->update(['status' => RegistrationApplication::STATUS_UNDER_REVIEW]);

        $reviewer = User::factory()->create(['user_id' => 'AD000001']);
        $reviewer->assignRole('admin');

        RegistrationReviewService::approve($application->fresh(), $reviewer);

        $this->assertDatabaseHas('users', ['email' => 'player@example.com']);
        $this->assertDatabaseHas('registration_applications', [
            'id' => $application->id,
            'status' => 'approved',
        ]);

        $account = User::where('email', 'player@example.com')->first();
        $this->assertTrue($account->hasRole('user'));
        $this->assertTrue($account->must_change_password);
        $this->assertStringStartsWith('PLR', $account->user_id);
        $this->assertNotNull($account->membership_expires_at);
        $this->assertSame('quarterly', $account->membership_period);

        Mail::assertSent(RegistrationApprovedMail::class);
    }

    public function test_rejection_notifies_applicant(): void
    {
        Storage::fake('local');
        Mail::fake();

        $this->post(route('register.individual.store'), $this->validPayload());
        $application = RegistrationApplication::first();
        $application->update(['status' => RegistrationApplication::STATUS_UNDER_REVIEW]);

        $reviewer = User::factory()->create(['user_id' => 'AD000002']);
        $reviewer->assignRole('admin');

        RegistrationReviewService::reject($application->fresh(), $reviewer, 'Incomplete documents');

        $this->assertDatabaseHas('registration_applications', [
            'id' => $application->id,
            'status' => 'rejected',
        ]);
        Mail::assertSent(RegistrationRejectedMail::class);
    }
}
