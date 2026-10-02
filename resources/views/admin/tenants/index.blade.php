<x-admin-layout>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">Tenants</h1>
            <a href="{{ route('admin.tenants.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">New tenant</a>
        </div>

        @if (session('status'))
            <p class="mb-4 text-sm text-green-700">{{ session('status') }}</p>
        @endif

        <div class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-950">
            <p class="font-medium mb-2">How domains work</p>
            <ul class="list-disc pl-5 space-y-1 text-indigo-900">
                <li><strong>Slug</strong> is an admin label only (e.g. <code class="text-xs bg-white/70 px-1 rounded">default</code>, <code class="text-xs bg-white/70 px-1 rounded">acme</code>). It is not used in URLs.</li>
                <li><strong>Domain</strong> is the hostname that selects a tenant — any valid hostname: shared apex (<code class="text-xs bg-white/70 px-1 rounded">lvh.me</code>), subdomain tenant (<code class="text-xs bg-white/70 px-1 rounded">tenant1.lvh.me</code>), or a separate site domain (<code class="text-xs bg-white/70 px-1 rounded">lvh2.me</code>).</li>
                <li>The <strong>Default</strong> tenant shows <code class="text-xs bg-white/70 px-1 rounded">*</code> only when it has <em>no</em> domain — then it catches every unlisted hostname. Once Default has a domain, only that hostname works for it; other hosts return 404.</li>
                <li>Registered tenant domains (e.g. <code class="text-xs bg-white/70 px-1 rounded">tenant1.lvh.me</code>) always serve that tenant — they are not treated as author or magazine subdomains.</li>
                <li>With author/magazine subdomains enabled, use <code class="text-xs bg-white/70 px-1 rounded">user.tenant1.lvh.me</code> and <code class="text-xs bg-white/70 px-1 rounded">magazine.tenant1.lvh.me</code> under each tenant domain.</li>
            </ul>
        </div>

        <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden bg-white">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Slug</th>
                    <th class="px-4 py-2">Domain</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($tenants as $tenant)
                    <tr>
                        <td class="px-4 py-2">{{ $tenant->name }}</td>
                        <td class="px-4 py-2">{{ $tenant->slug }}</td>
                        <td class="px-4 py-2">
                            @if ($tenant->isCatchAll())
                                <span class="font-mono text-indigo-700" title="Catch-all for unmapped hostnames">*</span>
                            @elseif ($url = \App\Support\Tenancy\Tenancy::publicUrlForHost($tenant->primaryHost()))
                                <a href="{{ $url }}" class="font-mono text-indigo-600 hover:underline" target="_blank" rel="noopener">{{ $tenant->primaryHost() }}</a>
                            @else
                                {{ $tenant->primaryHost() ?? '—' }}
                            @endif
                        </td>
                        <td class="px-4 py-2 capitalize">{{ $tenant->status }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.tenants.edit', $tenant) }}" class="text-indigo-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No tenants yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
