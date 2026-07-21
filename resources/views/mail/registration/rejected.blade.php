@component('mail::message')
# Registration Not Approved

Dear {{ $application->applicant_name }},

We regret to inform you that your **{{ ucfirst($application->type) }}** registration (Ref: **{{ $application->reference_no }}**) could not be approved at this time.

@if ($reason)
**Reason:** {{ $reason }}
@endif

Please review the details and **submit a new registration request**. We will be happy to review it again.

@component('mail::button', ['url' => route('register')])
Start a new registration
@endcomponent

Thanks,
{{ config('app.name') }}
@endcomponent
