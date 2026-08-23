<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Document;
use App\Models\OfficeBearer;
use App\Models\RegistrationApplication;
use App\Services\AadhaarNumberService;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

trait HandlesOfficeBearers
{
    protected function officeBearerRules(): array
    {
        return [
            'office_bearers' => ['required', 'array', 'min:7', 'max:14'],
            'office_bearers.*.name' => ['required', 'string', 'max:255'],
            'office_bearers.*.contact' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'office_bearers.*.address' => ['required', 'string', 'max:1000'],
            'office_bearers.*.phone' => ['nullable', 'string', 'max:20'],
            'office_bearers.*.email' => ['required', 'email', 'max:255'],
            'office_bearers.*.designation' => ['required', 'in:'.implode(',', array_keys(OfficeBearer::DESIGNATIONS))],
            'office_bearers.*.aadhaar' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'office_bearers.*.aadhaar_number' => ['required', 'digits:12'],
        ];
    }

    protected function officeBearerMessages(): array
    {
        return [
            'office_bearers.min' => 'At least 7 office bearers are required.',
            'office_bearers.max' => 'A maximum of 14 office bearers is allowed.',
            'office_bearers.*.contact.regex' => 'Each office bearer needs a valid 10-digit Indian mobile number.',
            'office_bearers.*.aadhaar_number.digits' => 'Each office bearer needs a 12-digit Aadhaar number.',
        ];
    }

    /**
     * Ensure exactly the Secretary designation is present at least once.
     */
    protected function validateSecretaryPresent(Validator $validator, array $bearers): void
    {
        $hasSecretary = collect($bearers)
            ->contains(fn ($b) => ($b['designation'] ?? null) === OfficeBearer::DESIGNATION_SECRETARY);

        if (! $hasSecretary) {
            $validator->errors()->add('office_bearers', 'One office bearer must be designated as Secretary.');
        }
    }

    protected function persistOfficeBearers(Request $request, RegistrationApplication $application, array $bearers): void
    {
        foreach ($bearers as $index => $bearer) {
            $record = $application->officeBearers()->create([
                'name' => $bearer['name'],
                'contact' => $bearer['contact'],
                'address' => $bearer['address'],
                'phone' => $bearer['phone'] ?? null,
                'email' => $bearer['email'],
                'designation' => $bearer['designation'],
                'aadhaar_number_masked' => AadhaarNumberService::mask($bearer['aadhaar_number']),
                'aadhaar_verification_status' => OfficeBearer::AADHAAR_STATUS_PENDING,
                'aadhaar_verification_note' => 'Awaiting Aadhaar OTP verification through the configured verification provider.',
            ]);

            if ($file = $request->file("office_bearers.$index.aadhaar")) {
                DocumentService::store($record, $file, Document::KIND_AADHAAR);
            }
        }
    }

    protected function secretaryEmail(array $bearers): ?string
    {
        return collect($bearers)
            ->firstWhere('designation', OfficeBearer::DESIGNATION_SECRETARY)['email'] ?? null;
    }
}
