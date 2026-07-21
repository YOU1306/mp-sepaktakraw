<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesOfficeBearers;
use App\Models\District;
use App\Models\Document;
use App\Models\OfficeBearer;
use App\Models\Player;
use App\Models\RegistrationApplication;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClubRegistrationController extends Controller
{
    use HandlesOfficeBearers;

    public function create(): View
    {
        return view('registration.club', [
            'districts' => District::query()->orderBy('name')->get(),
            'designations' => OfficeBearer::DESIGNATIONS,
            'fee' => PaymentService::feeForType(RegistrationApplication::TYPE_CLUB),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = array_merge([
            'club_name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:255'],
            'place' => ['required', 'string', 'max:255'],
            'district_id' => ['required', 'exists:districts,id'],
            'members' => ['required', 'array', 'min:1'],
            'members.*.member_role' => ['required', 'in:'.implode(',', array_keys(Player::MEMBER_ROLES))],
            'members.*.name' => ['required', 'string', 'max:255'],
            'members.*.father_name' => ['required', 'string', 'max:255'],
            'members.*.mother_name' => ['required', 'string', 'max:255'],
            'members.*.dob' => ['required', 'date', 'before:today'],
            'members.*.sex' => ['required', 'in:'.implode(',', array_keys(Player::SEXES))],
            'members.*.email' => ['required', 'email', 'max:255'],
            'members.*.contact' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'members.*.address' => ['required', 'string', 'max:1000'],
            'members.*.aadhaar' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'members.*.photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], $this->officeBearerRules());

        $validator = validator($request->all(), $rules, array_merge($this->officeBearerMessages(), [
            'members.min' => 'Add at least one club member.',
            'members.*.contact.regex' => 'Each member needs a valid 10-digit Indian mobile number.',
        ]));

        $validator->after(function ($v) use ($request) {
            $this->validateSecretaryPresent($v, $request->input('office_bearers', []));

            foreach ($request->input('members', []) as $i => $member) {
                if (($member['member_role'] ?? null) === Player::ROLE_PLAYER) {
                    if (empty($member['category'])) {
                        $v->errors()->add("members.$i.category", 'Category is required for players.');
                    }
                    if (! $request->hasFile("members.$i.marksheet")) {
                        $v->errors()->add("members.$i.marksheet", 'Marksheet is required for players.');
                    }
                    if (! $request->hasFile("members.$i.birth_certificate")) {
                        $v->errors()->add("members.$i.birth_certificate", 'Birth certificate is required for players.');
                    }
                }
            }
        });

        $validated = $validator->validate();

        $district = District::find($validated['district_id']);
        $bearers = $validated['office_bearers'];
        $members = $request->input('members');
        $fee = PaymentService::feeForType(RegistrationApplication::TYPE_CLUB);

        $application = DB::transaction(function () use ($request, $validated, $district, $bearers, $members, $fee) {
            $application = RegistrationApplication::create([
                'type' => RegistrationApplication::TYPE_CLUB,
                'reference_no' => RegistrationApplication::generateReference(RegistrationApplication::TYPE_CLUB),
                'status' => $fee > 0
                    ? RegistrationApplication::STATUS_PENDING_PAYMENT
                    : RegistrationApplication::STATUS_UNDER_REVIEW,
                'applicant_name' => $validated['club_name'],
                'applicant_email' => $this->secretaryEmail($bearers),
                'district_id' => $district->id,
                'submitted_at' => $fee > 0 ? null : now(),
                'expires_at' => $fee > 0 ? now()->addMinutes(PaymentService::windowMinutes()) : null,
            ]);

            $club = $application->club()->create([
                'club_name' => $validated['club_name'],
                'registration_number' => $validated['registration_number'],
                'place' => $validated['place'],
                'district_id' => $district->id,
            ]);

            $this->persistOfficeBearers($request, $application, $bearers);

            foreach ($members as $index => $member) {
                $isPlayer = $member['member_role'] === Player::ROLE_PLAYER;

                $player = $application->members()->create([
                    'club_id' => $club->id,
                    'member_role' => $member['member_role'],
                    'category' => $isPlayer ? ($member['category'] ?? null) : null,
                    'name' => $member['name'],
                    'father_name' => $member['father_name'],
                    'mother_name' => $member['mother_name'],
                    'dob' => $member['dob'],
                    'sex' => $member['sex'],
                    'email' => $member['email'],
                    'contact_number' => $member['contact'],
                    'address' => $member['address'],
                ]);

                DocumentService::store($player, $request->file("members.$index.aadhaar"), Document::KIND_AADHAAR);
                DocumentService::store($player, $request->file("members.$index.photo"), Document::KIND_PHOTO);

                if ($isPlayer) {
                    DocumentService::store($player, $request->file("members.$index.marksheet"), Document::KIND_MARKSHEET);
                    DocumentService::store($player, $request->file("members.$index.birth_certificate"), Document::KIND_BIRTH_CERTIFICATE);
                }
            }

            if ($fee > 0) {
                PaymentService::createOrder($application, $fee);
            }

            AuditService::logModel('submitted', $application, ['fee' => $fee, 'members' => count($members)]);

            return $application;
        });

        if ($fee > 0) {
            return redirect()->route('register.payment', $application->reference_no);
        }

        return redirect()->route('register.club.success', ['ref' => $application->reference_no]);
    }

    public function success(Request $request): View
    {
        return view('registration.success', [
            'reference' => $request->query('ref'),
            'type' => 'Club',
        ]);
    }
}
