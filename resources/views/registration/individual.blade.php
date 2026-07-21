@extends('layouts.public')

@section('title', 'Individual Player Registration')

@section('content')
    <x-reg.shell
        title="Individual Player Registration"
        subtitle="Register as a player (Sub-junior / Junior / Senior). This registration is free. Your application will be verified by the federation admin before approval.">

        <form method="POST" action="{{ route('register.individual.store') }}" enctype="multipart/form-data" class="space-y-2">
            @csrf

            <x-reg.section number="1" title="Player Details">
                <div class="grid sm:grid-cols-2 gap-5">
                    <x-reg.select name="category" label="Category" :options="$categories" required placeholder="Select category" />
                    <x-reg.select name="sex" label="Sex" :options="$sexes" required />
                    <div class="sm:col-span-2">
                        <x-reg.input name="name" label="Full name" required />
                    </div>
                    <x-reg.input name="father_name" label="Father's name" required />
                    <x-reg.input name="mother_name" label="Mother's name" required />
                    <div>
                        <x-reg.input name="dob" label="Date of birth" type="date" required :max="now()->toDateString()" />
                        <p id="age-display" class="text-xs font-medium text-emerald-700 mt-1"></p>
                    </div>
                    <x-reg.input name="contact_number" label="Contact number" type="tel" required
                        maxlength="10" pattern="[6-9][0-9]{9}" placeholder="9876543210"
                        hint="10-digit Indian mobile number" />
                </div>
            </x-reg.section>

            <x-reg.section number="2" title="Contact & Address">
                <div class="grid sm:grid-cols-2 gap-5">
                    <x-reg.input name="email" label="Email address" type="email" required
                        hint="Approval credentials will be sent here" />
                    <div class="sm:col-span-2">
                        <x-reg.textarea name="address" label="Full address" required rows="2" />
                    </div>
                </div>
            </x-reg.section>

            <x-reg.section number="3" title="Documents" description="Upload clear scans or photos. JPG, PNG or PDF, max 5 MB each.">
                <div class="grid sm:grid-cols-2 gap-5">
                    <x-reg.file name="photo" label="Passport photo" required accept=".jpg,.jpeg,.png" />
                    <x-reg.file name="aadhaar" label="Aadhaar card" required />
                    <x-reg.file name="marksheet" label="Recent marksheet / certificate" required />
                    <x-reg.file name="birth_certificate" label="Birth certificate" required />
                </div>
            </x-reg.section>

            <div class="pt-2 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between border-t border-stone-100">
                <p class="text-xs text-stone-500">By submitting, you confirm the information provided is true and correct.</p>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-8 py-2.5 rounded-lg shadow-sm">
                    Submit application
                </button>
            </div>
        </form>
    </x-reg.shell>

    <script>
        (function () {
            const dob = document.getElementById('dob');
            const out = document.getElementById('age-display');
            if (!dob) return;
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
        })();
    </script>
@endsection
