<x-admin-layout>
    @php
        $initialNavMode = old('magazines_nav_mode', $navMode);
        $sortableNav = $navPinningEnabled && $pinnedMagazines->isNotEmpty();
    @endphp

    <div
        class="max-w-4xl mx-auto px-4 py-8"
        x-data="{ mode: @js($initialNavMode) }"
    >
        <h1 class="text-3xl font-bold mb-2">{{ __('app.admin_magazines_nav') }}</h1>
        <p class="text-sm text-gray-600 mb-2">{{ __('app.admin_magazines_nav_help') }}</p>
        <p class="text-sm mb-6">
            <a href="{{ route('admin.magazines.manage') }}" class="text-indigo-600 hover:underline">{{ __('app.admin_magazines') }}</a>
        </p>

        @if (session('status') === 'magazine-nav-settings-updated')
            <p class="mb-4 text-sm text-green-700">{{ __('app.admin_magazine_nav_settings_updated') }}</p>
        @endif

        @if (session('status') === 'magazine-nav-updated')
            <p class="mb-4 text-sm text-green-700">{{ __('app.admin_magazine_nav_updated') }}</p>
        @endif

        @if (session('status') === 'magazine-nav-reordered')
            <p class="mb-4 text-sm text-green-700">{{ __('app.admin_magazine_nav_reordered') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.magazines.settings') }}" class="mb-8 rounded-lg border border-gray-200 bg-white p-4 space-y-4">
            @csrf
            @method('PATCH')
            <h2 class="text-sm font-semibold text-gray-900">{{ __('app.admin_magazines_nav_settings') }}</h2>

            <fieldset class="space-y-3">
                <legend class="text-sm font-medium text-gray-700">{{ __('app.magazines_nav_mode') }}</legend>
                <label class="flex items-start gap-3 rounded-md border border-gray-200 p-3 cursor-pointer has-[:checked]:border-gray-900 has-[:checked]:bg-gray-50">
                    <input
                        type="radio"
                        name="magazines_nav_mode"
                        value="manual"
                        x-model="mode"
                        class="mt-0.5 rounded-full border-gray-300"
                    >
                    <span>
                        <span class="block text-sm font-medium text-gray-900">{{ __('app.magazines_nav_mode_manual') }}</span>
                        <span class="block text-xs text-gray-500 mt-0.5">{{ __('app.magazines_nav_mode_manual_help') }}</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 rounded-md border border-gray-200 p-3 cursor-pointer has-[:checked]:border-gray-900 has-[:checked]:bg-gray-50">
                    <input
                        type="radio"
                        name="magazines_nav_mode"
                        value="auto"
                        x-model="mode"
                        class="mt-0.5 rounded-full border-gray-300"
                    >
                    <span>
                        <span class="block text-sm font-medium text-gray-900">{{ __('app.magazines_nav_mode_auto') }}</span>
                        <span class="block text-xs text-gray-500 mt-0.5">{{ __('app.magazines_nav_mode_auto_help') }}</span>
                    </span>
                </label>
            </fieldset>

            <div>
                <label for="magazines_nav_limit" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.magazines_nav_limit') }}</label>
                <input id="magazines_nav_limit" type="number" name="magazines_nav_limit" min="1" max="50" value="{{ old('magazines_nav_limit', $navLimit) }}" class="w-24 rounded-md border-gray-300 text-sm">
            </div>

            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">{{ __('app.save') }}</button>
        </form>

        @unless ($navPinningEnabled)
            <p class="mb-6 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">{{ __('app.admin_magazines_nav_migration_required') }}</p>
        @endunless

        @if ($navPinningEnabled)
            <div id="magazine-nav-manual-panel" x-show="mode === 'manual'" x-cloak>
                @if ($sortableNav)
                    <p class="mb-3 text-xs text-gray-500">{{ __('app.gallery_drag_reorder') }}</p>
                @endif

                <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            @if ($sortableNav)
                                <th class="w-10 px-2 py-2" aria-hidden="true"></th>
                            @endif
                            <th class="px-4 py-2">{{ __('app.magazine') }}</th>
                            <th class="px-4 py-2">{{ __('app.owner') }}</th>
                            <th class="px-4 py-2">{{ __('app.stories') }}</th>
                            <th class="px-4 py-2">{{ __('app.menu_order') }}</th>
                            <th class="px-4 py-2">{{ __('app.in_menu') }}</th>
                        </tr>
                    </thead>

                    @if ($pinnedMagazines->isNotEmpty())
                        <tbody id="magazine-nav-pinned" class="divide-y bg-white">
                            @foreach ($pinnedMagazines as $index => $magazine)
                                <tr data-magazine-id="{{ $magazine->id }}" class="bg-white">
                                    <td class="px-2 py-2 text-gray-400">
                                        <button
                                            type="button"
                                            draggable="true"
                                            data-drag-handle
                                            class="cursor-grab active:cursor-grabbing rounded p-1 hover:bg-gray-100 hover:text-gray-600"
                                            title="{{ __('app.gallery_drag_reorder') }}"
                                            aria-label="{{ __('app.gallery_drag_reorder') }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M7 5a1 1 0 110-2 1 1 0 010 2zm6 0a1 1 0 110-2 1 1 0 010 2zm-6 6a1 1 0 110-2 1 1 0 010 2zm6 0a1 1 0 110-2 1 1 0 010 2zm-6 6a1 1 0 110-2 1 1 0 010 2zm6 0a1 1 0 110-2 1 1 0 010 2z" />
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $magazine->name }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $magazine->owner?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ number_format($magazine->posts_count) }}</td>
                                    <td class="px-4 py-2 text-gray-600">
                                        <span data-order-display>{{ $index }}</span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <form method="POST" action="{{ route('admin.magazines.nav', $magazine) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="pinned" value="0">
                                            <button type="submit" class="rounded-full px-3 py-1 text-xs font-medium bg-gray-900 text-white">
                                                {{ __('app.remove_from_menu') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endif

                    <tbody class="divide-y">
                        @foreach ($unpinnedMagazines as $magazine)
                            <tr>
                                @if ($sortableNav)
                                    <td class="px-2 py-2"></td>
                                @endif
                                <td class="px-4 py-2 font-medium text-gray-900">{{ $magazine->name }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $magazine->owner?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ number_format($magazine->posts_count) }}</td>
                                <td class="px-4 py-2 text-gray-400">—</td>
                                <td class="px-4 py-2">
                                    <form method="POST" action="{{ route('admin.magazines.nav', $magazine) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="pinned" value="1">
                                        <button type="submit" class="rounded-full px-3 py-1 text-xs font-medium border border-gray-300 text-gray-700 hover:bg-gray-50">
                                            {{ __('app.add_to_menu') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($sortableNav)
                    <form id="magazine-nav-reorder-form" method="POST" action="{{ route('admin.magazines.nav.reorder') }}" class="hidden">
                        @csrf
                        @method('PATCH')
                        @foreach ($pinnedMagazines as $magazine)
                            <input type="hidden" name="magazine_ids[]" value="{{ $magazine->id }}">
                        @endforeach
                    </form>
                @endif
            </div>
        @endif
    </div>

    @if ($sortableNav ?? false)
        @push('scripts')
            <script>
                (function () {
                    const tbody = document.getElementById('magazine-nav-pinned');
                    const form = document.getElementById('magazine-nav-reorder-form');
                    if (!tbody || !form) {
                        return;
                    }

                    let draggedRow = null;

                    tbody.addEventListener('dragstart', (event) => {
                        const handle = event.target.closest('[data-drag-handle]');
                        if (!handle) {
                            event.preventDefault();
                            return;
                        }

                        draggedRow = handle.closest('tr[data-magazine-id]');
                        if (!draggedRow) {
                            event.preventDefault();
                            return;
                        }

                        event.dataTransfer.effectAllowed = 'move';
                        draggedRow.classList.add('opacity-50');
                    });

                    tbody.addEventListener('dragend', () => {
                        if (draggedRow) {
                            draggedRow.classList.remove('opacity-50');
                        }
                        draggedRow = null;
                    });

                    tbody.addEventListener('dragover', (event) => {
                        event.preventDefault();

                        if (!draggedRow) {
                            return;
                        }

                        const target = event.target.closest('tr[data-magazine-id]');
                        if (!target || target === draggedRow) {
                            return;
                        }

                        const rect = target.getBoundingClientRect();
                        const after = event.clientY > rect.top + (rect.height / 2);
                        tbody.insertBefore(draggedRow, after ? target.nextSibling : target);
                    });

                    tbody.addEventListener('drop', (event) => {
                        event.preventDefault();

                        if (!draggedRow) {
                            return;
                        }

                        form.querySelectorAll('input[name="magazine_ids[]"]').forEach((input) => input.remove());

                        tbody.querySelectorAll('tr[data-magazine-id]').forEach((row, index) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'magazine_ids[]';
                            input.value = row.dataset.magazineId;
                            form.appendChild(input);

                            const orderDisplay = row.querySelector('[data-order-display]');
                            if (orderDisplay) {
                                orderDisplay.textContent = String(index);
                            }
                        });

                        form.submit();
                    });
                })();
            </script>
        @endpush
    @endif
</x-admin-layout>
