@if (\App\Support\Tenancy\Tenancy::enabled() && \App\Support\Tenancy\TenantContext::allowsPlatformTenantManagement() && auth()->user()?->isSuperAdmin())
    @php
        $tenants = \App\Models\Tenant::query()->where('is_platform', false)->orderBy('name')->get(['id', 'name']);
        $selectedTenantId = session('admin_tenant_id');
    @endphp
    <form method="POST" action="{{ route('admin.tenant-scope.update') }}" class="px-1">
        @csrf
        <label for="admin_tenant_scope" class="block px-2 mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
            Tenant scope
        </label>
        <select
            id="admin_tenant_scope"
            name="tenant_id"
            onchange="this.form.submit()"
            class="w-full text-sm bg-gray-800 border border-gray-700 text-white rounded-md py-2 px-2"
        >
            <option value="" @selected($selectedTenantId === null)>All tenants</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected((string) $selectedTenantId === (string) $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
        </select>
    </form>
@endif
