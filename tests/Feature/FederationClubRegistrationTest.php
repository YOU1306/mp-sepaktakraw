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

class FederationClubRegistrationTest extends TestCase
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

    public function test_federation_registration_persists_application_and_bearers(): void
    {
        Storage::fake('local');
        $district = $this->district();

        $response = $this->post(route('register.federation.store'), [
            'registration_number' => 'FED-REG-001',
            'district_id' => $district->id,
            'acknowledgement' => UploadedFile::fake()->create('ack.pdf', 100, 'application/pdf'),
            'office_bearers' => $this->bearers(7),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('registration_applications', [
            'type' => 'federation',
            'status' => 'under_review',
            'applicant_email' => 'bearer0@example.com',
        ]);
        $this->assertDatabaseHas('federations', ['registration_number' => 'FED-REG-001']);

        $application = RegistrationApplication::first();
        $this->assertCount(7, $application->officeBearers);
        $this->assertNotNull($application->secretaryBearer);
        $this->assertCount(1, $application->documents); // acknowledgement
    }

    public function test_federation_requires_a_secretary(): void
    {
        Storage::fake('local');
        $district = $this->district();

        $this->post(route('register.federation.store'), [
            'registration_number' => 'FED-REG-002',
            'district_id' => $district->id,
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
            'acknowledgement' => UploadedFile::fake()->create('ack.pdf', 100, 'application/pdf'),
            'office_bearers' => $this->bearers(5),
        ])->assertSessionHasErrors('office_bearers');
    }

    public function test_club_registration_persists_players_and_officials(): void
    {
        Storage::fake('local');
        $district = $this->district();

        $members = [
            [
                'member_role' => 'player',
                'category' => 'junior',
                'name' => 'Player One',
                'father_name' => 'Father',
                'mother_name' => 'Mother',
                'dob' => '2010-01-01',
                'sex' => 'male',
                'email' => 'player1@example.com',
                'contact' => '9876543210',
                'address' => 'Club address',
                'aadhaar' => UploadedFile::fake()->create('a.pdf', 80, 'application/pdf'),
                'photo' => UploadedFile::fake()->image('p.jpg'),
                'marksheet' => UploadedFile::fake()->create('m.pdf', 80, 'application/pdf'),
                'birth_certificate' => UploadedFile::fake()->create('b.pdf', 80, 'application/pdf'),
            ],
            [
                'member_role' => 'coach',
                'name' => 'Coach One',
                'father_name' => 'Father',
                'mother_name' => 'Mother',
                'dob' => '1990-01-01',
                'sex' => 'male',
                'email' => 'coach1@example.com',
                'contact' => '9876543210',
                'address' => 'Club address',
                'aadhaar' => UploadedFile::fake()->create('a.pdf', 80, 'application/pdf'),
                'photo' => UploadedFile::fake()->image('p.jpg'),
            ],
        ];

        $response = $this->post(route('register.club.store'), [
            'club_name' => 'Bhopal Kraw Club',
            'registration_number' => 'CLB-REG-001',
            'place' => 'Bhopal',
            'district_id' => $district->id,
            'office_bearers' => $this->bearers(7),
            'members' => $members,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('registration_applications', ['type' => 'club', 'status' => 'under_review']);
        $this->assertDatabaseHas('clubs', ['club_name' => 'Bhopal Kraw Club', 'registration_number' => 'CLB-REG-001']);

        $application = RegistrationApplication::first();
        $this->assertCount(7, $application->officeBearers);
        $this->assertCount(2, $application->members);

        $player = $application->members->firstWhere('member_role', 'player');
        $coach = $application->members->firstWhere('member_role', 'coach');
        $this->assertSame('junior', $player->category);
        $this->assertNull($coach->category);
        $this->assertCount(4, $player->documents); // aadhaar, photo, marksheet, birth
        $this->assertCount(2, $coach->documents); // aadhaar, photo
    }

    public function test_club_player_requires_marksheet_and_birth_certificate(): void
    {
        Storage::fake('local');
        $district = $this->district();

        $members = [[
            'member_role' => 'player',
            'category' => 'junior',
            'name' => 'Player One',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'dob' => '2010-01-01',
            'sex' => 'male',
            'email' => 'player1@example.com',
            'contact' => '9876543210',
            'address' => 'Club address',
            'aadhaar' => UploadedFile::fake()->create('a.pdf', 80, 'application/pdf'),
            'photo' => UploadedFile::fake()->image('p.jpg'),
            // marksheet + birth_certificate intentionally missing
        ]];

        $this->post(route('register.club.store'), [
            'club_name' => 'Bhopal Kraw Club',
            'registration_number' => 'CLB-REG-002',
            'place' => 'Bhopal',
            'district_id' => $district->id,
            'office_bearers' => $this->bearers(7),
            'members' => $members,
        ])->assertSessionHasErrors(['members.0.marksheet', 'members.0.birth_certificate']);
    }
}
