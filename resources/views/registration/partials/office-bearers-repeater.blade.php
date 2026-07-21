@php
    $obRows = old('office_bearers') ?: array_fill(0, 7, []);
@endphp

<x-reg.section number="{{ $sectionNumber ?? 2 }}" title="Office Bearers"
    description="Minimum 7, maximum 14. One must be designated as Secretary (login credentials are issued to the Secretary).">

    <div id="ob-container" class="space-y-4">
        @foreach ($obRows as $i => $row)
            @include('registration.partials.office-bearer-row', ['index' => $i, 'data' => $row])
        @endforeach
    </div>

    <div class="mt-4 flex items-center justify-between">
        <button type="button" id="ob-add"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-800 border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg">
            <span class="text-base leading-none">+</span> Add office bearer
        </button>
        <span class="text-xs text-stone-500"><span id="ob-count">0</span> / 14 added</span>
    </div>

    <template id="ob-template">
        @include('registration.partials.office-bearer-row', ['index' => '__INDEX__', 'data' => []])
    </template>
</x-reg.section>

<script>
    (function () {
        const container = document.getElementById('ob-container');
        const template = document.getElementById('ob-template');
        const addBtn = document.getElementById('ob-add');
        const countEl = document.getElementById('ob-count');
        const MIN = 7, MAX = 14;
        let counter = container.querySelectorAll('.ob-row').length;

        function renumber() {
            const rows = container.querySelectorAll('.ob-row');
            rows.forEach((row, i) => {
                row.querySelector('.ob-num').textContent = '#' + (i + 1);
                const rm = row.querySelector('.ob-remove');
                rm.style.display = rows.length > MIN ? '' : 'none';
            });
            countEl.textContent = rows.length;
            addBtn.disabled = rows.length >= MAX;
            addBtn.classList.toggle('opacity-50', rows.length >= MAX);
            addBtn.classList.toggle('cursor-not-allowed', rows.length >= MAX);
        }

        addBtn.addEventListener('click', function () {
            if (container.querySelectorAll('.ob-row').length >= MAX) return;
            const html = template.innerHTML.replaceAll('__INDEX__', counter++);
            const wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            container.appendChild(wrap.firstElementChild);
            renumber();
        });

        container.addEventListener('click', function (e) {
            if (e.target.classList.contains('ob-remove')) {
                if (container.querySelectorAll('.ob-row').length <= MIN) return;
                e.target.closest('.ob-row').remove();
                renumber();
            }
        });

        renumber();
    })();
</script>
