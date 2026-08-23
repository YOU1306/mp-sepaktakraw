@extends('layouts.public')

@section('title', 'Individual Registration')

@section('content')
    <x-reg.shell
        title="Individual Registration"
        subtitle="Register as a Player, Team Manager, Coach, Referee, Scorer or Official. Your phone and email are verified with a one-time code. Aadhaar verification will be completed securely by OTP after submission.">

        <form method="POST" action="{{ route('register.individual.store') }}" enctype="multipart/form-data" id="individual-form" class="space-y-2">
            @csrf
            <input type="hidden" name="phone_token" id="phone_token">
            <input type="hidden" name="email_token" id="email_token">

            <x-reg.section number="1" title="Register As">
                <div class="grid sm:grid-cols-3 gap-3 mb-4">
                    @foreach ($memberRoles as $value => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-stone-300 px-3 py-2.5 cursor-pointer hover:border-emerald-500 [&:has(input:checked)]:border-emerald-600 [&:has(input:checked)]:bg-emerald-50">
                            <input type="radio" name="member_role" value="{{ $value }}" id="role-{{ $value }}" required
                                @checked(old('member_role') === $value) class="text-emerald-600 focus:ring-emerald-600">
                            <span class="text-sm font-medium text-stone-800">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div id="category-field" class="hidden">
                    <x-reg.select name="category" label="Category" :options="$categories" placeholder="Select category" />
                </div>
            </x-reg.section>

            <x-reg.section number="2" title="Personal Details">
                <div class="grid sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <x-reg.input name="name" label="Full name" required />
                    </div>
                    <x-reg.input name="father_name" label="Father's name" required />
                    <x-reg.input name="mother_name" label="Mother's name" required />
                    <div>
                        <x-reg.input name="dob" label="Date of birth" type="date" required :max="now()->toDateString()" />
                        <p id="age-display" class="text-xs font-medium text-emerald-700 mt-1"></p>
                    </div>
                    <x-reg.select name="sex" label="Sex" :options="$sexes" required />
                    <x-reg.select name="district_id" label="District" :options="$districts->pluck('name', 'id')" required placeholder="Select district"
                        hint="Your district federation reviews and approves this application." />
                </div>
            </x-reg.section>

            <x-reg.section number="3" title="Contact Details" description="Both your email and mobile number must be verified with a one-time code before you can submit.">
                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <x-reg.input name="email" label="Email address" type="email" required id="email" />
                        <div class="otp-widget mt-2" data-channel="email" data-field="#email" data-token="#email_token">
                            <button type="button" class="otp-send text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-md hover:bg-emerald-100">Send code</button>
                            <div class="otp-input-row hidden mt-2 flex items-center gap-2">
                                <input type="text" maxlength="6" placeholder="6-digit code" class="otp-code w-32 rounded-lg border border-stone-300 px-3 py-1.5 text-sm">
                                <button type="button" class="otp-verify text-xs font-semibold text-white bg-emerald-600 px-3 py-1.5 rounded-md hover:bg-emerald-700">Verify</button>
                            </div>
                            <p class="otp-status text-xs mt-1"></p>
                        </div>
                    </div>
                    <div>
                        <x-reg.input name="contact_number" label="Contact number" type="tel" required
                            maxlength="10" pattern="[6-9][0-9]{9}" placeholder="9876543210" id="contact_number"
                            hint="10-digit Indian mobile number" />
                        <div class="otp-widget mt-2" data-channel="phone" data-field="#contact_number" data-token="#phone_token">
                            <button type="button" class="otp-send text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-md hover:bg-emerald-100">Send code</button>
                            <div class="otp-input-row hidden mt-2 flex items-center gap-2">
                                <input type="text" maxlength="6" placeholder="6-digit code" class="otp-code w-32 rounded-lg border border-stone-300 px-3 py-1.5 text-sm">
                                <button type="button" class="otp-verify text-xs font-semibold text-white bg-emerald-600 px-3 py-1.5 rounded-md hover:bg-emerald-700">Verify</button>
                            </div>
                            <p class="otp-status text-xs mt-1"></p>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <x-reg.textarea name="address" label="Full address" required rows="2" />
                    </div>
                </div>
            </x-reg.section>

            <x-reg.section number="4" title="Documents" description="Upload a clear Aadhaar PDF or image and enter the Aadhaar number. JPG, PNG or PDF, max 5 MB each.">
                <div class="grid sm:grid-cols-2 gap-5">
                    <x-reg.file name="photo" label="Passport photo" required accept=".jpg,.jpeg,.png" />
                    <div>
                        <x-reg.file name="aadhaar" label="Aadhaar card" required accept=".jpg,.jpeg,.png,.pdf"
                            hint="Upload a clear scan or photograph of the Aadhaar card. It will be stored privately for review." />
                        <div class="mt-2">
                            <x-reg.input name="aadhaar_number" label="Aadhaar number" required inputmode="numeric" maxlength="12" pattern="[0-9]{12}"
                                hint="Enter the 12-digit number. The linked mobile number will be verified by OTP when Signzy is connected." />
                        </div>
                    </div>
                    <div id="marksheet-field" class="hidden">
                        <x-reg.file name="marksheet" label="Recent marksheet / certificate" />
                    </div>
                    <div id="birth-field" class="hidden">
                        <x-reg.file name="birth_certificate" label="Birth certificate" />
                    </div>
                </div>
            </x-reg.section>

            <x-reg.section number="5" title="Registration Fee" description="Choose a billing period. Payment is completed on the next step.">
                <fieldset class="space-y-2">
                    <legend class="sr-only">Billing period</legend>
                    @foreach ($periods as $value => $label)
                        <label class="flex items-center justify-between rounded-lg border border-stone-300 px-4 py-3 cursor-pointer hover:border-emerald-500 [&:has(input:checked)]:border-emerald-600 [&:has(input:checked)]:bg-emerald-50">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="billing_period" value="{{ $value }}" required @checked($loop->first) class="text-emerald-600 focus:ring-emerald-600">
                                <span class="text-sm font-medium text-stone-800">{{ $label }}</span>
                            </span>
                            <span class="text-sm font-semibold text-stone-900">₹{{ number_format($fees[$value] ?? 0) }}</span>
                        </label>
                    @endforeach
                </fieldset>
            </x-reg.section>

            <div class="pt-2 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between border-t border-stone-100">
                <p class="text-xs text-stone-500">By submitting, you confirm the information provided is true and correct.</p>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-8 py-2.5 rounded-lg shadow-sm">
                    Continue to payment
                </button>
            </div>
        </form>
    </x-reg.shell>

    <script>
        (function () {
            const dob = document.getElementById('dob');
            const out = document.getElementById('age-display');
            if (dob) {
                function calc() {
                    if (!dob.value) { out.textContent = ''; return; }
                    const b = new Date(dob.value);
                    const now = new Date();
                    if (b > now) { out.textContent = ''; return; }
                    let years = now.getFullYear() - b.getFullYear();
                    const anniversary = new Date(b); anniversary.setFullYear(b.getFullYear() + years);
                    if (anniversary > now) years--;
                    const last = new Date(b); last.setFullYear(b.getFullYear() + years);
                    const days = Math.floor((now - last) / 86400000);
                    out.textContent = 'Age: ' + years + ' years, ' + days + ' days';
                }
                dob.addEventListener('change', calc);
                calc();
            }

            function toggleRoleFields() {
                const checked = document.querySelector('input[name="member_role"]:checked');
                const isPlayer = checked && checked.value === 'player';
                ['category-field', 'marksheet-field', 'birth-field'].forEach(id => {
                    const el = document.getElementById(id);
                    el.classList.toggle('hidden', !isPlayer);
                    const field = el.querySelector('input, select');
                    if (field) {
                        if (isPlayer) {
                            field.setAttribute('required', 'required');
                        } else {
                            field.removeAttribute('required');
                            if (field.type === 'file') {
                                field.value = '';
                                if (window.RegDropzone) window.RegDropzone.reset(field);
                            } else {
                                field.value = '';
                            }
                        }
                    }
                });
            }
            document.querySelectorAll('input[name="member_role"]').forEach(el => el.addEventListener('change', toggleRoleFields));
            toggleRoleFields();

            document.querySelectorAll('.otp-widget').forEach(function (widget) {
                const channel = widget.dataset.channel;
                const field = document.querySelector(widget.dataset.field);
                const tokenField = document.querySelector(widget.dataset.token);
                const sendBtn = widget.querySelector('.otp-send');
                const verifyBtn = widget.querySelector('.otp-verify');
                const codeInput = widget.querySelector('.otp-code');
                const inputRow = widget.querySelector('.otp-input-row');
                const status = widget.querySelector('.otp-status');
                let lastVerifiedValue = null;

                field.addEventListener('input', function () {
                    if (tokenField.value && field.value !== lastVerifiedValue) {
                        tokenField.value = '';
                        status.textContent = 'Value changed — please verify again.';
                        status.className = 'otp-status text-xs mt-1 text-amber-600';
                    }
                });

                sendBtn.addEventListener('click', function () {
                    if (!field.value) {
                        status.textContent = 'Enter a value above first.';
                        status.className = 'otp-status text-xs mt-1 text-red-600';
                        return;
                    }
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Sending…';
                    fetch('{{ route('register.otp.send') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ channel: channel, destination: field.value }),
                    }).then(r => r.json().then(data => ({ ok: r.ok, data })))
                        .then(({ ok, data }) => {
                            sendBtn.disabled = false;
                            sendBtn.textContent = 'Resend code';
                            if (!ok) {
                                status.textContent = data.message || 'Could not send code.';
                                status.className = 'otp-status text-xs mt-1 text-red-600';
                                return;
                            }
                            widget.dataset.otpToken = data.token;
                            inputRow.classList.remove('hidden');
                            status.textContent = data.test_code
                                ? ('Code sent. Test mode code: ' + data.test_code)
                                : 'Code sent. Check your ' + (channel === 'phone' ? 'SMS' : 'inbox') + '.';
                            status.className = 'otp-status text-xs mt-1 text-stone-600';
                        }).catch(() => {
                            sendBtn.disabled = false;
                            sendBtn.textContent = 'Send code';
                            status.textContent = 'Network error. Try again.';
                            status.className = 'otp-status text-xs mt-1 text-red-600';
                        });
                });

                verifyBtn.addEventListener('click', function () {
                    if (!widget.dataset.otpToken || !codeInput.value) return;
                    verifyBtn.disabled = true;
                    fetch('{{ route('register.otp.verify') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ token: widget.dataset.otpToken, code: codeInput.value }),
                    }).then(r => r.json().then(data => ({ ok: r.ok, data })))
                        .then(({ ok, data }) => {
                            verifyBtn.disabled = false;
                            if (!ok || !data.verified) {
                                status.textContent = data.message || 'Incorrect code.';
                                status.className = 'otp-status text-xs mt-1 text-red-600';
                                return;
                            }
                            tokenField.value = widget.dataset.otpToken;
                            lastVerifiedValue = field.value;
                            status.textContent = '✓ Verified.';
                            status.className = 'otp-status text-xs mt-1 text-emerald-700 font-medium';
                            inputRow.classList.add('hidden');
                            sendBtn.textContent = 'Verified ✓';
                            sendBtn.disabled = true;
                        });
                });
            });
        })();
    </script>
@endsection
