<?php

namespace Tests\Feature;

use App\Console\Commands\SendMembershipReminders;
use App\Models\District;
use App\Models\User;
use App\Notifications\MembershipExpiringNotification;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MembershipLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    private function member(array $overrides = []): User
    {
        $district = District::firstOrCreate(['code' => 'BPL'], ['name' => 'Bhopal']);

        $user = User::factory()->create(array_merge([
            'user_id' => 'PLR000099',
            'district_id' => $district->id,
            'membership_period' => 'quarterly',
            'membership_expires_at' => now()->subDay(),
        ], $overrides));
        $user->assignRole('user');

        return $user;
    }

    public function test_expired_member_is_redirected_to_renewal_page(): void
    {
        $user = $this->member();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('membership.renew'));
    }

    public function test_expired_member_can_still_reach_and_pay_the_renewal_page(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get(route('membership.renew'))->assertOk();

        $this->actingAs($user)
            ->post(route('membership.renew.process'), ['billing_period' => 'quarterly'])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertTrue($user->membership_expires_at->isFuture());
        $this->assertFalse($user->isMembershipExpired());
    }

    public function test_active_member_is_not_blocked(): void
    {
        $user = $this->member(['membership_expires_at' => now()->addMonths(2)]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_reminder_command_notifies_members_expiring_within_ten_days(): void
    {
        Notification::fake();

        $dueSoon = $this->member(['user_id' => 'PLR000098', 'membership_expires_at' => now()->addDays(5)]);
        $notDue = $this->member(['user_id' => 'PLR000097', 'membership_expires_at' => now()->addDays(20)]);

        $this->artisan(SendMembershipReminders::class)->assertSuccessful();

        Notification::assertSentTo($dueSoon, MembershipExpiringNotification::class);
        Notification::assertNotSentTo($notDue, MembershipExpiringNotification::class);

        $this->assertNotNull($dueSoon->fresh()->membership_reminder_sent_at);
    }
}
