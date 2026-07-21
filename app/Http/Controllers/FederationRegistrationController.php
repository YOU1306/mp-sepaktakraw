<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesOfficeBearers;
use App\Models\District;
use App\Models\Document;
use App\Models\OfficeBearer;
use App\Models\RegistrationApplication;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FederationRegistrationController extends Controller
{
    use HandlesOfficeBearers;

    public function create(): View
    {
        return view('registration.federation', [
            'districts' => District::query()->orderBy('name')->get(),
            'designations' => OfficeBearer::DESIGNATIONS,
            'fee' => PaymentService::feeForType(RegistrationApplication::TYPE_FEDERATION),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = validator($request->all(), array_merge([
            'registration_number' => ['required', 'string', 'max:255'],
            'district_id' => ['required', 'exists:districts,id'],
            'acknowledgement' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], $this->officeBearerRules()), $this->officeBearerMessages());

        $validator->after(function ($v) use ($request) {
            $this->validateSecretaryPresent($v, $request->input('office_bearers', []));
        });

        $validated = $validator->validate();

        $district = District::find($validated['district_id']);
        $bearers = $validated['office_bearers'];
        $fee = PaymentService::feeForType(RegistrationApplication::TYPE_FEDERATION);

        $application = DB::transaction(function () use ($request, $validated, $district, $bearers, $fee) {
            $application = RegistrationApplication::create([
                'type' => RegistrationApplication::TYPE_FEDERATION,
                'reference_no' => RegistrationApplication::generateReference(RegistrationApplication::TYPE_FEDERATION),
                'status' => $fee > 0
                    ? RegistrationApplication::STATUS_PENDING_PAYMENT
                    : RegistrationApplication::STATUS_UNDER_REVIEW,
                'applicant_name' => $district->name.' District Federation',
                'applicant_email' => $this->secretaryEmail($bearers),
                'district_id' => $district->id,
                'submitted_at' => $fee > 0 ? null : now(),
                'expires_at' => $fee > 0 ? now()->addMinutes(PaymentService::windowMinutes()) : null,
            ]);

            $application->federation()->create([
                'registration_number' => $validated['registration_number'],
                'district_id' => $district->id,
            ]);

            DocumentService::store($application, $request->file('acknowledgement'), Document::KIND_ACKNOWLEDGEMENT);

            $this->persistOfficeBearers($request, $application, $bearers);

            if ($fee > 0) {
                PaymentService::createOrder($application, $fee);
            }

            AuditService::logModel('submitted', $application, ['fee' => $fee]);

            return $application;
        });

        if ($fee > 0) {
            return redirect()->route('register.payment', $application->reference_no);
        }

        return redirect()->route('register.federation.success', ['ref' => $application->reference_no]);
    }

    public function success(Request $request): View
    {
        return view('registration.success', [
            'reference' => $request->query('ref'),
            'type' => 'District Federation',
        ]);
    }
}
