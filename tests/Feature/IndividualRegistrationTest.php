<?php

namespace Tests\Feature;

use App\Mail\RegistrationApprovedMail;
use App\Mail\RegistrationRejectedMail;
use App\Models\RegistrationApplication;
use App\Models\User;
use App\Services\RegistrationReviewService;
use Database\Seeders\RoleSeeder;
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
    }

    private function validPayload(): array
    {
        return [
            'category' => 'junior',
            'name' => 'Test Player',
            'father_name' => 'Father Name',
            'mother_name' => 'Mother Name',
            'dob' => '2010-01-01',
            'sex' => 'male',
            'email' => 'player@example.com',
            'address' => '123 Test Street, Bhopal',
            'contact_number' => '9876543210',
            'aadhaar' => UploadedFile::fake()->create('aadhaar.pdf', 100, 'application/pdf'),
            'marksheet' => UploadedFile::fake()->create('marksheet.pdf', 100, 'application/pdf'),
            'photo' => UploadedFile::fake()->image('photo.jpg'),
            'birth_certificate' => UploadedFile::fake()->create('birth.pdf', 100, 'application/pdf'),
        ];
    }

    public function test_individual_registration_creates_application_and_documents(): void
    {
        Storage::fake('local');

        $response = $this->post(route('register.individual.store'), $this->validPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('registration_applications', [
            'type' => 'individual',
            'status' => 'under_review',
            'applicant_email' => 'player@example.com',
        ]);
        $this->assertDatabaseHas('players', ['name' => 'Test Player', 'category' => 'junior']);

        $application = RegistrationApplication::first();
        $this->assertCount(4, $application->player->documents);
    }

    public function test_invalid_contact_number_is_rejected(): void
    {
        Storage::fake('local');

        $payload = $this->validPayload();
        $payload['contact_number'] = '12345';

        $this->post(route('register.individual.store'), $payload)
            ->assertSessionHasErrors('contact_number');
    }

    public function test_approval_creates_account_and_sends_credentials(): void
    {
        Storage::fake('local');
        Mail::fake();

        $this->post(route('register.individual.store'), $this->validPayload());
        $application = RegistrationApplication::first();

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

        Mail::assertSent(RegistrationApprovedMail::class);
    }

    public function test_rejection_notifies_applicant(): void
    {
        Storage::fake('local');
        Mail::fake();

        $this->post(route('register.individual.store'), $this->validPayload());
        $application = RegistrationApplication::first();

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
