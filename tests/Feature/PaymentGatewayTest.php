<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Payment;
use App\Models\RegistrationApplication;
use App\Models\User;
use App\Services\PaymentService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    private function pendingApplication(): RegistrationApplication
    {
        $district = District::firstOrCreate(['code' => 'BPL'], ['name' => 'Bhopal']);

        $application = RegistrationApplication::query()->create([
            'type' => RegistrationApplication::TYPE_INDIVIDUAL,
            'reference_no' => 'IND'.now()->format('ymd').'0001',
            'status' => RegistrationApplication::STATUS_PENDING_PAYMENT,
            'applicant_name' => 'Test Player',
            'applicant_email' => 'player@example.com',
            'applicant_phone' => '9876543210',
            'district_id' => $district->id,
            'billing_period' => 'quarterly',
            'expires_at' => now()->addMinutes(30),
        ]);

        $application->payments()->create([
            'amount' => 100,
            'currency' => 'INR',
            'gateway_order_id' => 'order_TEST123',
            'status' => Payment::STATUS_CREATED,
            'billing_period' => 'quarterly',
        ]);

        return $application->fresh(['payment']);
    }

    private function enableLiveKeys(): void
    {
        config([
            'services.razorpay.key' => 'rzp_test_key',
            'services.razorpay.secret' => 'test_secret_value',
            'services.razorpay.webhook_secret' => 'whsec_test_secret',
        ]);
    }

    private function signCheckout(string $orderId, string $paymentId): string
    {
        return hash_hmac('sha256', $orderId.'|'.$paymentId, (string) config('services.razorpay.secret'));
    }

    private function signWebhook(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, (string) config('services.razorpay.webhook_secret'));
    }

    public function test_is_test_mode_when_keys_are_blank(): void
    {
        config(['services.razorpay.key' => null, 'services.razorpay.secret' => null]);

        $this->assertTrue(PaymentService::isTestMode());
    }

    public function test_test_mode_process_marks_registration_paid(): void
    {
        config(['services.razorpay.key' => null, 'services.razorpay.secret' => null]);

        $application = $this->pendingApplication();

        $this->post(route('register.payment.process', $application->reference_no))
            ->assertRedirect(route('register.individual.success', ['ref' => $application->reference_no]));

        $this->assertSame(Payment::STATUS_PAID, $application->payment->fresh()->status);
        $this->assertSame(RegistrationApplication::STATUS_UNDER_REVIEW, $application->fresh()->status);
    }

    public function test_live_mode_process_requires_valid_signature(): void
    {
        $this->enableLiveKeys();
        $application = $this->pendingApplication();

        $this->post(route('register.payment.process', $application->reference_no), [
            'razorpay_order_id' => $application->payment->gateway_order_id,
            'razorpay_payment_id' => 'pay_fake',
            'razorpay_signature' => 'not-a-valid-signature',
        ])->assertStatus(400);

        $this->assertSame(Payment::STATUS_CREATED, $application->payment->fresh()->status);
    }

    public function test_live_mode_process_rejects_order_mismatch(): void
    {
        $this->enableLiveKeys();
        $application = $this->pendingApplication();

        $signature = $this->signCheckout('order_OTHER', 'pay_abc');

        $this->post(route('register.payment.process', $application->reference_no), [
            'razorpay_order_id' => 'order_OTHER',
            'razorpay_payment_id' => 'pay_abc',
            'razorpay_signature' => $signature,
        ])->assertStatus(400);

        $this->assertSame(Payment::STATUS_CREATED, $application->payment->fresh()->status);
    }

    public function test_live_mode_process_marks_paid_with_valid_signature(): void
    {
        $this->enableLiveKeys();
        $application = $this->pendingApplication();
        $orderId = $application->payment->gateway_order_id;
        $paymentId = 'pay_VALID123';

        $this->post(route('register.payment.process', $application->reference_no), [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $this->signCheckout($orderId, $paymentId),
        ])->assertRedirect(route('register.individual.success', ['ref' => $application->reference_no]));

        $payment = $application->payment->fresh();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertSame($paymentId, $payment->gateway_payment_id);
        $this->assertSame(RegistrationApplication::STATUS_UNDER_REVIEW, $application->fresh()->status);
    }

    public function test_payment_page_exposes_checkout_when_keys_configured(): void
    {
        $this->enableLiveKeys();
        $application = $this->pendingApplication();

        $this->get(route('register.payment', $application->reference_no))
            ->assertOk()
            ->assertSee('checkout.razorpay.com', false)
            ->assertSee('rzp-button', false)
            ->assertDontSee('Test Mode');
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        $this->enableLiveKeys();
        $body = json_encode(['event' => 'payment.captured']);

        $this->call('POST', route('webhooks.payment'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Razorpay-Signature' => 'bad-signature',
        ], $body)->assertStatus(400);
    }

    public function test_webhook_marks_payment_paid_on_payment_captured(): void
    {
        $this->enableLiveKeys();
        $application = $this->pendingApplication();
        $orderId = $application->payment->gateway_order_id;

        $body = json_encode([
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_WEBHOOK1',
                        'order_id' => $orderId,
                    ],
                ],
            ],
        ]);

        $this->call('POST', route('webhooks.payment'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Razorpay-Signature' => $this->signWebhook($body),
        ], $body)->assertOk()->assertJson(['verified' => true]);

        $this->assertSame(Payment::STATUS_PAID, $application->payment->fresh()->status);
        $this->assertSame('pay_WEBHOOK1', $application->payment->fresh()->gateway_payment_id);
        $this->assertSame(RegistrationApplication::STATUS_UNDER_REVIEW, $application->fresh()->status);
    }

    public function test_webhook_is_idempotent_when_already_paid(): void
    {
        $this->enableLiveKeys();
        $application = $this->pendingApplication();
        PaymentService::markPaid($application->payment, 'pay_FIRST');

        $body = json_encode([
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_SECOND',
                        'order_id' => $application->payment->gateway_order_id,
                    ],
                ],
            ],
        ]);

        $this->call('POST', route('webhooks.payment'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Razorpay-Signature' => $this->signWebhook($body),
        ], $body)->assertOk()->assertJson(['verified' => true, 'idempotent' => true]);

        $this->assertSame('pay_FIRST', $application->payment->fresh()->gateway_payment_id);
    }

    public function test_mark_paid_does_not_double_extend_membership(): void
    {
        $this->enableLiveKeys();

        $user = User::factory()->create([
            'user_id' => 'PLR000050',
            'membership_period' => 'quarterly',
            'membership_expires_at' => now()->addDays(10),
        ]);
        $user->assignRole('user');

        $payment = $user->payments()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'currency' => 'INR',
            'gateway_order_id' => 'order_MEM1',
            'status' => Payment::STATUS_CREATED,
            'billing_period' => 'quarterly',
        ]);

        $originalExpiry = $user->membership_expires_at->copy();

        PaymentService::markPaid($payment, 'pay_A');
        PaymentService::markPaid($payment->fresh(), 'pay_B');

        $user->refresh();
        $this->assertTrue($user->membership_expires_at->equalTo($originalExpiry->copy()->addMonths(3)));
        $this->assertSame('pay_A', $payment->fresh()->gateway_payment_id);
    }

    public function test_membership_confirm_accepts_valid_signature(): void
    {
        $this->enableLiveKeys();

        $user = User::factory()->create([
            'user_id' => 'PLR000051',
            'membership_period' => 'quarterly',
            'membership_expires_at' => now()->subDay(),
        ]);
        $user->assignRole('user');

        $payment = $user->payments()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'currency' => 'INR',
            'gateway_order_id' => 'order_MEM2',
            'status' => Payment::STATUS_CREATED,
            'billing_period' => 'quarterly',
        ]);

        $paymentId = 'pay_MEM2';
        $this->actingAs($user)
            ->post(route('membership.renew.confirm', $payment), [
                'razorpay_order_id' => $payment->gateway_order_id,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $this->signCheckout($payment->gateway_order_id, $paymentId),
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertTrue($user->fresh()->membership_expires_at->isFuture());
    }

    public function test_config_exposes_webhook_secret(): void
    {
        config(['services.razorpay.webhook_secret' => 'from_env']);

        $this->assertSame('from_env', config('services.razorpay.webhook_secret'));
    }
}
