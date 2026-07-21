@php
    $data = $data ?? [];
    $v = fn ($key) => is_numeric($index) ? old("members.$index.$key", $data[$key] ?? '') : '';
    $inputCls = 'w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none';
    $selectedRole = is_numeric($index) ? old("members.$index.member_role", $data['member_role'] ?? '') : '';
    $isPlayer = $selectedRole === \App\Models\Player::ROLE_PLAYER;
@endphp
<div class="mb-row rounded-lg border border-stone-200 bg-stone-50/60 p-4" data-index="{{ $index }}">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-stone-900">Member <span class="mb-num"></span></h3>
        <button type="button" class="mb-remove text-xs font-medium text-red-600 hover:text-red-700">Remove</button>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Role <span class="text-red-500">*</span></label>
            <select name="members[{{ $index }}][member_role]" required class="mb-role {{ $inputCls }} bg-white">
                <option value="">Select role</option>
                @foreach (\App\Models\Player::MEMBER_ROLES as $val => $label)
                    <option value="{{ $val }}" @selected($selectedRole === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-category" style="{{ $isPlayer ? '' : 'display:none' }}">
            <label class="block text-xs font-medium text-stone-600 mb-1">Category <span class="text-red-500">*</span></label>
            <select name="members[{{ $index }}][category]" class="{{ $inputCls }} bg-white">
                <option value="">Select category</option>
                @foreach (\App\Models\Player::CATEGORIES as $val => $label)
                    <option value="{{ $val }}" @selected($v('category') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Full name <span class="text-red-500">*</span></label>
            <input type="text" name="members[{{ $index }}][name]" value="{{ $v('name') }}" required class="{{ $inputCls }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Sex <span class="text-red-500">*</span></label>
            <select name="members[{{ $index }}][sex]" required class="{{ $inputCls }} bg-white">
                <option value="">Select</option>
                @foreach (\App\Models\Player::SEXES as $val => $label)
                    <option value="{{ $val }}" @selected($v('sex') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Father's name <span class="text-red-500">*</span></label>
            <input type="text" name="members[{{ $index }}][father_name]" value="{{ $v('father_name') }}" required class="{{ $inputCls }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Mother's name <span class="text-red-500">*</span></label>
            <input type="text" name="members[{{ $index }}][mother_name]" value="{{ $v('mother_name') }}" required class="{{ $inputCls }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Date of birth <span class="text-red-500">*</span></label>
            <input type="date" name="members[{{ $index }}][dob]" value="{{ $v('dob') }}" required max="{{ now()->toDateString() }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Contact number <span class="text-red-500">*</span></label>
            <input type="tel" name="members[{{ $index }}][contact]" value="{{ $v('contact') }}" required maxlength="10" pattern="[6-9][0-9]{9}" placeholder="10-digit mobile" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" name="members[{{ $index }}][email]" value="{{ $v('email') }}" required class="{{ $inputCls }}">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-stone-600 mb-1">Address <span class="text-red-500">*</span></label>
            <textarea name="members[{{ $index }}][address]" rows="2" required class="{{ $inputCls }}">{{ $v('address') }}</textarea>
        </div>
        <div>
            <x-reg.file name="members[{{ $index }}][photo]" label="Photo" accept=".jpg,.jpeg,.png" required />
        </div>
        <div>
            <x-reg.file name="members[{{ $index }}][aadhaar]" label="Aadhaar card" required />
        </div>
        <div class="mb-marksheet" style="{{ $isPlayer ? '' : 'display:none' }}">
            <x-reg.file name="members[{{ $index }}][marksheet]" label="Recent marksheet / certificate" :required="$isPlayer" />
        </div>
        <div class="mb-birth" style="{{ $isPlayer ? '' : 'display:none' }}">
            <x-reg.file name="members[{{ $index }}][birth_certificate]" label="Birth certificate" :required="$isPlayer" />
        </div>
    </div>
</div>
