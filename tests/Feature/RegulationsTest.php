<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Regulation;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegulationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    private function makeRegulation(array $overrides = []): Regulation
    {
        Storage::disk('local')->put('regulations/sample.pdf', '%PDF-1.4 fake content');

        return Regulation::create(array_merge([
            'title' => 'Law of the Game 2024 - Double',
            'path' => 'regulations/sample.pdf',
            'size' => 21,
            'sort_order' => 1,
            'is_active' => true,
        ], $overrides));
    }

    public function test_public_index_lists_only_active_regulations(): void
    {
        $this->makeRegulation(['is_active' => true]);
        $this->makeRegulation(['title' => 'Draft Rule', 'is_active' => false]);

        $response = $this->get(route('regulations.index'));

        $response->assertOk();
        $response->assertSee('Law of the Game 2024 - Double');
        $response->assertDontSee('Draft Rule');
    }

    public function test_guest_can_view_an_active_regulation_pdf(): void
    {
        $regulation = $this->makeRegulation();

        $response = $this->get(route('regulations.show', $regulation));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_guest_cannot_view_an_inactive_regulation_pdf(): void
    {
        $regulation = $this->makeRegulation(['is_active' => false]);

        $this->get(route('regulations.show', $regulation))->assertNotFound();
    }

    public function test_admin_can_preview_an_inactive_regulation_pdf(): void
    {
        $regulation = $this->makeRegulation(['is_active' => false]);
        $admin = User::factory()->create(['user_id' => 'AD000001']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('regulations.show', $regulation))
            ->assertOk();
    }

    public function test_admin_can_manage_regulations_in_filament(): void
    {
        $admin = User::factory()->create(['user_id' => 'AD000002', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/admin/regulations')
            ->assertOk();
    }

    public function test_super_user_cannot_manage_regulations_in_filament(): void
    {
        $district = District::create(['name' => 'Indore', 'code' => 'IND']);
        $superUser = User::factory()->create([
            'user_id' => 'SU000001',
            'district_id' => $district->id,
            'status' => User::STATUS_ACTIVE,
        ]);
        $superUser->assignRole('super-user');

        $this->actingAs($superUser)
            ->get('/admin/regulations')
            ->assertForbidden();
    }
}
