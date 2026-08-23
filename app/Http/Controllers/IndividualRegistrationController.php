<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Document;
use App\Models\Player;
use App\Models\RegistrationApplication;
use App\Models\Setting;
use App\Models\VerificationCode;
use App\Services\AadhaarNumberService;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\OtpService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IndividualRegistrationController extends Controller
{
    public function create(): View
    {
        return view('registration.individual', [
            'categories' => Player::CATEGORIES,
            'sexes' => Player::SEXES,
            'memberRoles' => Player::MEMBER_ROLES,
            'districts' => District::query()->orderBy('name')->get(),
            'periods' => Setting::PERIODS,
            'fees' => Setting::feesForType('individual'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $docRules = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
        $photoRules = ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'];

        $validator = validator($request->all(), [
            'member_role' => ['required', 'in:'.implode(',', array_keys(Player::MEMBER_ROLES))],
            'category' => ['nullable', 'in:'.implode(',', array_keys(Player::CATEGORIES))],
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'mother_name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date', 'before:today'],
            'sex' => ['required', 'in:'.implode(',', array_keys(Player::SEXES))],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_number' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'district_id' => ['required', 'exists:districts,id'],
            'billing_period' => ['required', 'in:'.implode(',', array_keys(Setting::PERIODS))],
            'aadhaar' => $docRules,
            'aadhaar_number' => ['required', 'digits:12'],
            'photo' => $photoRules,
            'phone_token' => ['required', 'string'],
            'email_token' => ['required', 'string'],
        ], [
            'contact_number.regex' => 'Enter a valid 10-digit Indian mobile number.',
        ]);

        $validator->after(function ($v) use ($request) {
            $isPlayer = $request->input('member_role') === Player::ROLE_PLAYER;

            if ($isPlayer) {
                if (empty($request->input('category'))) {
                    $v->errors()->add('category', 'Category is required for players.');
                }
                if (! $request->hasFile('marksheet')) {
                    $v->errors()->add('marksheet', 'Marksheet is required for players.');
                }
                if (! $request->hasFile('birth_certificate')) {
                    $v->errors()->add('birth_certificate', 'Birth certificate is required for players.');
                }
            }

            if (! OtpService::isVerifiedFor($request->input('phone_token'), VerificationCode::CHANNEL_PHONE, (string) $request->input('contact_number'))) {
                $v->errors()->add('phone_token', 'Please verify your contact number with the OTP sent to it.');
            }

            if (! OtpService::isVerifiedFor($request->input('email_token'), VerificationCode::CHANNEL_EMAIL, (string) $request->input('email'))) {
                $v->errors()->add('email_token', 'Please verify your email address with the code sent to it.');
            }

            if (! AadhaarNumberService::isValid((string) $request->input('aadhaar_number'))) {
                $v->errors()->add('aadhaar_number', 'Enter a valid 12-digit Aadhaar number.');
            }
        });

        $validated = $validator->validate();

        $isPlayer = $validated['member_role'] === Player::ROLE_PLAYER;
        $district = District::find($validated['district_id']);
        $fee = PaymentService::feeForPeriod('individual', $validated['billing_period']);

        $application = DB::transaction(function () use ($request, $validated, $isPlayer, $district, $fee) {
            $application = RegistrationApplication::create([
                'type' => RegistrationApplication::TYPE_INDIVIDUAL,
                'reference_no' => RegistrationApplication::generateReference(RegistrationApplication::TYPE_INDIVIDUAL),
                'status' => $fee > 0
                    ? RegistrationApplication::STATUS_PENDING_PAYMENT
                    : RegistrationApplication::STATUS_UNDER_REVIEW,
                'applicant_name' => $validated['name'],
                'applicant_email' => $validated['email'],
                'applicant_phone' => $validated['contact_number'],
                'district_id' => $district->id,
                'billing_period' => $validated['billing_period'],
                'submitted_at' => $fee > 0 ? null : now(),
                'expires_at' => $fee > 0 ? now()->addMinutes(PaymentService::windowMinutes()) : null,
            ]);

            $player = Player::create([
                'application_id' => $application->id,
                'member_role' => $validated['member_role'],
                'category' => $isPlayer ? $validated['category'] : null,
                'name' => $validated['name'],
                'father_name' => $validated['father_name'],
                'mother_name' => $validated['mother_name'],
                'dob' => $validated['dob'],
                'sex' => $validated['sex'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'],
                'address' => $validated['address'],
            ]);

            DocumentService::store($player, $request->file('photo'), Document::KIND_PHOTO);
            $aadhaarDoc = DocumentService::store($player, $request->file('aadhaar'), Document::KIND_AADHAAR);

            $player->update([
                'aadhaar_verified' => false,
                'aadhaar_number_masked' => AadhaarNumberService::mask($validated['aadhaar_number']),
                'aadhaar_kyc_data' => null,
                'aadhaar_kyc_note' => 'Awaiting Aadhaar OTP verification through the configured verification provider.',
                'aadhaar_identity_match' => null,
                'aadhaar_verification_status' => Player::AADHAAR_STATUS_PENDING,
            ]);

            if ($isPlayer) {
                DocumentService::store($player, $request->file('marksheet'), Document::KIND_MARKSHEET);
                DocumentService::store($player, $request->file('birth_certificate'), Document::KIND_BIRTH_CERTIFICATE);
            }

            if ($fee > 0) {
                PaymentService::createOrder($application, $fee, $validated['billing_period']);
            }

            AuditService::logModel('submitted', $application, [
                'fee' => $fee,
                'member_role' => $validated['member_role'],
                'aadhaar_verified' => false,
                'aadhaar_verification_status' => Player::AADHAAR_STATUS_PENDING,
            ]);

            return $application;
        });

        if ($fee > 0) {
            return redirect()->route('register.payment', $application->reference_no);
        }

        return redirect()->route('register.individual.success', ['ref' => $application->reference_no]);
    }

    public function success(Request $request): View
    {
        return view('registration.success', [
            'reference' => $request->query('ref'),
            'type' => 'Individual',
        ]);
    }
}
