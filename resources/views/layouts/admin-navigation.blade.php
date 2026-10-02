<aside class="w-full lg:w-56 shrink-0 lg:sticky lg:top-8">
    <nav aria-label="Admin" class="flex flex-col bg-gray-900 text-white rounded-lg p-3">
        <div class="mb-2">
            <a href="{{ route('admin.dashboard') }}" @class([
                'block text-lg font-bold px-3 py-2 rounded-md',
                'text-white bg-gray-800' => request()->routeIs('admin.dashboard'),
                'text-white hover:text-gray-200 hover:bg-gray-800' => ! request()->routeIs('admin.dashboard'),
            ])>
                Admin
            </a>
        </div>

        <div class="space-y-1">
            <x-admin.nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                Users
            </x-admin.nav-link>

            <x-admin.nav-link :href="route('admin.posts.index')" :active="request()->routeIs('admin.posts.*')">
                Posts
            </x-admin.nav-link>

            <x-admin.nav-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
                Categories
            </x-admin.nav-link>

            <x-admin.nav-link :href="route('admin.tags.index')" :active="request()->routeIs('admin.tags.*')">
                Tags
            </x-admin.nav-link>

            <x-admin.nav-link :href="route('admin.custom-fields.edit')" :active="request()->routeIs('admin.custom-fields.*')">
                Custom fields
            </x-admin.nav-link>

            @feature('category_requests')
                <x-admin.nav-link :href="route('admin.category-requests.index')" :active="request()->routeIs('admin.category-requests.*')">
                    Category requests
                </x-admin.nav-link>
            @endfeature

            @feature('user_reports')
                <x-admin.nav-link :href="route('admin.user-reports.index')" :active="request()->routeIs('admin.user-reports.*')">
                    User reports
                </x-admin.nav-link>
            @endfeature

            @if (auth()->user()->isSuperAdmin())
                @feature('multi_tenancy')
                    @if (\App\Support\Tenancy\TenantContext::allowsPlatformTenantManagement())
                        <x-admin.nav-link :href="route('admin.tenants.index')" :active="request()->routeIs('admin.tenants.*')">
                            Tenants
                        </x-admin.nav-link>
                    @endif
                @endfeature

                <x-admin.nav-link :href="route('admin.settings.edit')" :active="request()->routeIs('admin.settings.*')">
                    Settings
                </x-admin.nav-link>

                <x-admin.nav-link :href="route('admin.trash.index')" :active="request()->routeIs('admin.trash.*')">
                    Trash
                </x-admin.nav-link>
            @endif
        </div>

        <div class="mt-4 pt-4 border-t border-gray-800 space-y-3">
            @include('partials.admin.tenant-scope-selector')
        </div>
    </nav>
</aside>
