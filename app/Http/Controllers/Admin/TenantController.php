<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Rules\ValidTenantHost;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantResolver;
use App\Support\Tenancy\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::query()
            ->with('domains')
            ->where('is_platform', false)
            ->orderBy('name')
            ->get();

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        return view('admin.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'host' => ['required', 'string', 'max:253', new ValidTenantHost(), Rule::unique('tenant_domains', 'host')],
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        $tenant = Tenant::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'status' => $data['status'],
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => TenantDomain::normalizeHost($data['host']),
            'is_primary' => true,
        ]);

        TenantResolver::flushHostCache(TenantDomain::normalizeHost($data['host']));
        Tenancy::flushRegisteredTenantHostsCache();

        return redirect()->route('admin.tenants.index')->with('status', 'Tenant created.');
    }

    public function edit(Tenant $tenant): View
    {
        abort_if($tenant->is_platform, 404);

        $tenant->load('domains');

        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_if($tenant->is_platform, 404);

        $primaryDomain = $tenant->domains()->where('is_primary', true)->first();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'host' => [
                'required',
                'string',
                'max:253',
                new ValidTenantHost(),
                Rule::unique('tenant_domains', 'host')->ignore($primaryDomain?->id),
            ],
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        $normalizedHost = TenantDomain::normalizeHost($data['host']);
        $previousHost = $primaryDomain?->host;

        $tenant->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'status' => $data['status'],
        ]);

        if ($primaryDomain) {
            $primaryDomain->update(['host' => $normalizedHost]);
        } else {
            TenantDomain::query()->create([
                'tenant_id' => $tenant->id,
                'host' => $normalizedHost,
                'is_primary' => true,
            ]);
        }

        if ($previousHost && $previousHost !== $normalizedHost) {
            TenantResolver::flushHostCache($previousHost);
        }

        TenantResolver::flushHostCache($normalizedHost);
        Tenancy::flushRegisteredTenantHostsCache();

        return redirect()->route('admin.tenants.index')->with('status', 'Tenant updated.');
    }

    public function updateScope(Request $request): RedirectResponse
    {
        if (! Tenancy::enabled() || ! TenantContext::allowsPlatformTenantManagement($request->getHost())) {
            abort(404);
        }

        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', Rule::exists('tenants', 'id')->where('is_platform', false)],
        ]);

        if ($data['tenant_id'] === null) {
            $request->session()->forget('admin_tenant_id');
        } else {
            $request->session()->put('admin_tenant_id', (int) $data['tenant_id']);
        }

        return redirect()->back()->with('status', 'Admin tenant scope updated.');
    }
}
