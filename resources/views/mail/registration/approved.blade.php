@component('mail::message')
# Registration Approved

Dear {{ $application->applicant_name }},

Your **{{ ucfirst($application->type) }}** registration (Ref: **{{ $application->reference_no }}**) has been **approved** by the Madhya Pradesh Sepaktakraw Federation.

You can now log in to the portal using the credentials below:

@component('mail::panel')
**User ID:** {{ $userId }}
**Temporary Password:** {{ $temporaryPassword }}
@endcomponent

For your security, you will be asked to change this password the first time you log in.

@component('mail::button', ['url' => route('login')])
Log in to the portal
@endcomponent

Thanks,
{{ config('app.name') }}
@endcomponent
