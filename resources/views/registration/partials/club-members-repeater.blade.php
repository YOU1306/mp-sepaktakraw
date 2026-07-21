@php
    $memberRows = old('members') ?: array_fill(0, 1, []);
@endphp

<x-reg.section number="{{ $sectionNumber ?? 3 }}" title="Club Members"
    description="Add players and officials. Players require a category, marksheet and birth certificate; officials (Team Manager, Coach, Referee, Scorer, Official) do not.">

    <div id="mb-container" class="space-y-4">
        @foreach ($memberRows as $i => $row)
            @include('registration.partials.club-member-row', ['index' => $i, 'data' => $row])
        @endforeach
    </div>

    <div class="mt-4 flex items-center justify-between">
        <button type="button" id="mb-add"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-800 border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg">
            <span class="text-base leading-none">+</span> Add member
        </button>
        <span class="text-xs text-stone-500"><span id="mb-count">0</span> member(s)</span>
    </div>

    <template id="mb-template">
        @include('registration.partials.club-member-row', ['index' => '__INDEX__', 'data' => []])
    </template>
</x-reg.section>

<script>
    (function () {
        const container = document.getElementById('mb-container');
        const template = document.getElementById('mb-template');
        const addBtn = document.getElementById('mb-add');
        const countEl = document.getElementById('mb-count');
        const MIN = 1;
        let counter = container.querySelectorAll('.mb-row').length;

        function togglePlayerFields(row) {
            const role = row.querySelector('.mb-role').value;
            const isPlayer = role === 'player';
            row.querySelectorAll('.mb-category, .mb-marksheet, .mb-birth').forEach(el => {
                el.style.display = isPlayer ? '' : 'none';
                const field = el.querySelector('input, select');
                if (field) {
                    if (isPlayer) {
                        field.setAttribute('required', 'required');
                    } else {
                        field.removeAttribute('required');
                        if (field.type === 'file') {
                            field.value = '';
                            if (window.RegDropzone) window.RegDropzone.reset(field);
                        }
                    }
                }
            });
        }

        function renumber() {
            const rows = container.querySelectorAll('.mb-row');
            rows.forEach((row, i) => {
                row.querySelector('.mb-num').textContent = '#' + (i + 1);
                row.querySelector('.mb-remove').style.display = rows.length > MIN ? '' : 'none';
            });
            countEl.textContent = rows.length;
        }

        addBtn.addEventListener('click', function () {
            const html = template.innerHTML.replaceAll('__INDEX__', counter++);
            const wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            const row = wrap.firstElementChild;
            container.appendChild(row);
            togglePlayerFields(row);
            renumber();
        });

        container.addEventListener('click', function (e) {
            if (e.target.classList.contains('mb-remove')) {
                if (container.querySelectorAll('.mb-row').length <= MIN) return;
                e.target.closest('.mb-row').remove();
                renumber();
            }
        });

        container.addEventListener('change', function (e) {
            if (e.target.classList.contains('mb-role')) {
                togglePlayerFields(e.target.closest('.mb-row'));
            }
        });

        container.querySelectorAll('.mb-row').forEach(togglePlayerFields);
        renumber();
    })();
</script>
