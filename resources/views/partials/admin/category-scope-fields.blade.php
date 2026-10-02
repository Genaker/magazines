@props([
    'magazines',
    'selectedMagazineId' => null,
    'selectedParentId' => null,
    'excludeCategoryId' => null,
])

<div
    x-data="{
        magazineId: @js(old('magazine_id', $selectedMagazineId) !== null ? (string) old('magazine_id', $selectedMagazineId) : ''),
        parentId: @js(old('parent_id', $selectedParentId) !== null ? (string) old('parent_id', $selectedParentId) : ''),
        parentOptions: [],
        loadingParents: false,
        parentOptionsUrl: @js(route('admin.categories.parent-options')),
        excludeId: @js($excludeCategoryId),
        magazines: @js($magazines->map(fn ($magazine) => ['id' => (string) $magazine->id, 'name' => $magazine->name])->values()),
        siteWideLabel: @js(__('app.admin_category_magazine_site_wide')),
        parentSiteWideHelp: @js(__('app.admin_category_parent_site_wide')),
        parentMagazineHelpPrefix: @js(__('app.admin_category_parent_magazine', ['name' => '__NAME__'])),
        scopeSiteBanner: @js(__('app.admin_category_scope_site_banner')),
        scopeMagazineBannerPrefix: @js(__('app.admin_category_scope_magazine_banner', ['name' => '__NAME__'])),
        isSiteWide() {
            return this.magazineId === '';
        },
        selectedMagazineName() {
            if (this.isSiteWide()) {
                return this.siteWideLabel;
            }

            return this.magazines.find((magazine) => magazine.id === this.magazineId)?.name ?? '';
        },
        scopeBannerText() {
            if (this.isSiteWide()) {
                return this.scopeSiteBanner;
            }

            const name = this.selectedMagazineName();

            return name !== ''
                ? this.scopeMagazineBannerPrefix.replace('__NAME__', name)
                : '';
        },
        parentHelpText() {
            if (this.isSiteWide()) {
                return this.parentSiteWideHelp;
            }

            const name = this.selectedMagazineName();

            return name !== ''
                ? this.parentMagazineHelpPrefix.replace('__NAME__', name)
                : '';
        },
        indent(depth) {
            return '\u2014 '.repeat(Math.max(0, depth));
        },
        async loadParents() {
            this.loadingParents = true;

            try {
                const params = new URLSearchParams();
                if (this.magazineId !== '') {
                    params.set('magazine_id', this.magazineId);
                }
                if (this.excludeId) {
                    params.set('exclude', this.excludeId);
                }

                const response = await fetch(`${this.parentOptionsUrl}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();
                this.parentOptions = data.options ?? [];

                if (this.parentId !== '' && ! this.parentOptions.some((option) => String(option.id) === String(this.parentId))) {
                    this.parentId = '';
                }
            } finally {
                this.loadingParents = false;
            }
        },
        onMagazineChange() {
            this.loadParents();
        },
    }"
    x-init="loadParents()"
>
    @if ($magazines->isNotEmpty())
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Magazine</label>
            <select
                name="magazine_id"
                class="w-full rounded-md border-gray-300"
                x-model="magazineId"
                @change="onMagazineChange()"
            >
                <option value="">{{ __('app.admin_category_magazine_site_wide') }}</option>
                @foreach ($magazines as $magazine)
                    <option value="{{ $magazine->id }}">{{ $magazine->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">{{ __('app.admin_category_magazine_help') }}</p>
        </div>

        <div
            class="mb-4 rounded-md border px-3 py-2 text-sm"
            :class="isSiteWide() ? 'border-gray-200 bg-gray-50 text-gray-700' : 'border-indigo-200 bg-indigo-50 text-indigo-900'"
            x-text="scopeBannerText()"
            x-cloak
        ></div>
    @else
        <p class="mb-4 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
            {{ __('app.admin_category_site_wide_only') }}
        </p>
    @endif

    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Parent category</label>
        <div class="relative">
            <select
                name="parent_id"
                class="w-full rounded-md border-gray-300 disabled:bg-gray-50 disabled:text-gray-500"
                x-model="parentId"
                :disabled="loadingParents"
                :aria-busy="loadingParents"
            >
                <option value="">— Top level —</option>
                <template x-for="option in parentOptions" :key="option.id">
                    <option
                        :value="option.id"
                        x-text="`${indent(option.depth)}${option.name}`"
                    ></option>
                </template>
            </select>
            <div
                x-show="loadingParents"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="absolute inset-0 flex items-center justify-center rounded-md bg-white/85"
                aria-hidden="true"
            >
                <span class="admin-category-parent-spinner inline-flex h-6 w-6 items-center justify-center" role="status" aria-label="Loading">
                    <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-1" x-text="parentHelpText()" x-cloak></p>
    </div>

    <style>
        @keyframes admin-category-parent-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .admin-category-parent-spinner {
            animation: admin-category-parent-spin 0.7s linear infinite;
            transform-origin: center;
        }
    </style>
</div>
