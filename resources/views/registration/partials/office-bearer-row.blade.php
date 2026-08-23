@php
    // $index may be an integer (server-rendered) or the literal string __INDEX__ (JS template).
    $data = $data ?? [];
    $v = fn ($key) => is_numeric($index) ? old("office_bearers.$index.$key", $data[$key] ?? '') : '';
@endphp
<div class="ob-row rounded-lg border border-stone-200 bg-stone-50/60 p-4" data-index="{{ $index }}">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-stone-900">Office Bearer <span class="ob-num"></span></h3>
        <button type="button" class="ob-remove text-xs font-medium text-red-600 hover:text-red-700">Remove</button>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" name="office_bearers[{{ $index }}][name]" value="{{ $v('name') }}" required
                class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Designation <span class="text-red-500">*</span></label>
            <select name="office_bearers[{{ $index }}][designation]" required
                class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none">
                <option value="">Select</option>
                @foreach (\App\Models\OfficeBearer::DESIGNATIONS as $val => $label)
                    <option value="{{ $val }}" @selected($v('designation') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Contact number <span class="text-red-500">*</span></label>
            <input type="tel" name="office_bearers[{{ $index }}][contact]" value="{{ $v('contact') }}" required
                maxlength="10" pattern="[6-9][0-9]{9}" placeholder="10-digit mobile"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Phone (optional)</label>
            <input type="tel" name="office_bearers[{{ $index }}][phone]" value="{{ $v('phone') }}"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" name="office_bearers[{{ $index }}][email]" value="{{ $v('email') }}" required
                class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none">
        </div>
        <div>
            <x-reg.file name="office_bearers[{{ $index }}][aadhaar]" label="Aadhaar card" required />
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Aadhaar number <span class="text-red-500">*</span></label>
            <input type="text" name="office_bearers[{{ $index }}][aadhaar_number]" value="{{ $v('aadhaar_number') }}" required
                inputmode="numeric" maxlength="12" pattern="[0-9]{12}"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none">
            <p class="text-xs text-stone-500 mt-1">The linked mobile number will be verified by OTP when Signzy is connected.</p>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-stone-600 mb-1">Address <span class="text-red-500">*</span></label>
            <textarea name="office_bearers[{{ $index }}][address]" rows="2" required
                class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none">{{ $v('address') }}</textarea>
        </div>
    </div>
</div>
