<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Player;
use App\Models\RegistrationApplication;
use App\Services\AuditService;
use App\Services\DocumentService;
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $docRules = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
        $photoRules = ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'];

        $validated = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_keys(Player::CATEGORIES))],
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'mother_name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date', 'before:today'],
            'sex' => ['required', 'in:'.implode(',', array_keys(Player::SEXES))],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_number' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'aadhaar' => $docRules,
            'marksheet' => $docRules,
            'photo' => $photoRules,
            'birth_certificate' => $docRules,
        ], [
            'contact_number.regex' => 'Enter a valid 10-digit Indian mobile number.',
        ]);

        $application = DB::transaction(function () use ($request, $validated) {
            $application = RegistrationApplication::create([
                'type' => RegistrationApplication::TYPE_INDIVIDUAL,
                'reference_no' => RegistrationApplication::generateReference(RegistrationApplication::TYPE_INDIVIDUAL),
                'status' => RegistrationApplication::STATUS_UNDER_REVIEW,
                'applicant_name' => $validated['name'],
                'applicant_email' => $validated['email'],
                'submitted_at' => now(),
            ]);

            $player = Player::create([
                'application_id' => $application->id,
                'category' => $validated['category'],
                'name' => $validated['name'],
                'father_name' => $validated['father_name'],
                'mother_name' => $validated['mother_name'],
                'dob' => $validated['dob'],
                'sex' => $validated['sex'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'],
                'address' => $validated['address'],
            ]);

            DocumentService::store($player, $request->file('aadhaar'), Document::KIND_AADHAAR);
            DocumentService::store($player, $request->file('marksheet'), Document::KIND_MARKSHEET);
            DocumentService::store($player, $request->file('photo'), Document::KIND_PHOTO);
            DocumentService::store($player, $request->file('birth_certificate'), Document::KIND_BIRTH_CERTIFICATE);

            AuditService::logModel('submitted', $application);

            return $application;
        });

        return redirect()->route('register.individual.success', ['ref' => $application->reference_no]);
    }

    public function success(Request $request): View
    {
        return view('registration.success', [
            'reference' => $request->query('ref'),
            'type' => 'Individual Player',
        ]);
    }
}
