<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Magazine;
use App\Models\Post;
use App\Support\AdminGrid;
use App\Support\CustomFieldSchema;
use App\Support\CustomFields;
use App\Support\EntityCache;
use App\Support\MagazineNavSettings;
use App\Support\Slugger;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MagazineController extends Controller
{
    public function manage(Request $request): View
    {
        ['sort' => $sort, 'dir' => $dir, 'search' => $search] = AdminGrid::params(
            $request,
            ['name', 'slug', 'created_at'],
            'name',
        );

        $magazines = Magazine::query()
            ->with('owner')
            ->withCount('posts')
            ->when($search !== '', fn ($query) => AdminGrid::applySearch($query, $search, ['name', 'slug']))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.magazines.manage', compact('magazines', 'search', 'sort', 'dir'));
    }

    public function edit(Magazine $magazine): View
    {
        return view('admin.magazines.edit', compact('magazine'));
    }

    public function update(Request $request, Magazine $magazine, ImageService $images): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'require_post_approval' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'image', 'max:'.config('media.max_upload_kb')],
            ...CustomFields::validationRules(CustomFieldSchema::CONTEXT_MAGAZINE),
        ]);

        $slug = $magazine->slug;

        if ($data['name'] !== $magazine->name) {
            $slug = Slugger::unique($data['name'], new Magazine, 'slug', $magazine->id);
        }

        $magazine->update([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'require_post_approval' => $request->boolean('require_post_approval'),
            'custom_fields' => CustomFields::collect($request->input('custom_fields'), CustomFieldSchema::CONTEXT_MAGAZINE),
        ]);

        if ($request->hasFile('logo')) {
            $processed = $images->processUpload($request->file('logo'), 'magazines');
            $magazine->update([
                'logo' => $processed['path'],
                'logo_variants' => $processed['variants'],
            ]);
        }

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        return redirect()->route('admin.magazines.manage')->with('status', 'magazine-updated');
    }

    public function destroy(Magazine $magazine): RedirectResponse
    {
        $magazine->update([
            'nav_pinned_at' => null,
        ]);
        $magazine->delete();

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        return redirect()->route('admin.magazines.manage')->with('status', 'magazine-deleted');
    }

    public function posts(Request $request, Magazine $magazine): View
    {
        ['sort' => $sort, 'dir' => $dir, 'search' => $search] = AdminGrid::params(
            $request,
            ['title', 'status', 'views_count', 'created_at'],
            'created_at',
        );

        $status = $request->query('status');

        $posts = Post::query()
            ->where('magazine_id', $magazine->id)
            ->with(['user', 'category'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($status, ['draft', 'published', 'unlisted'], true), fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.magazines.posts', compact('magazine', 'posts', 'search', 'sort', 'dir', 'status'));
    }

    public function index(): View
    {
        $query = Magazine::query()
            ->with('owner')
            ->withCount(['posts' => fn ($query) => $query->published()])
            ->orderByRaw('nav_pinned_at IS NULL')
            ->orderBy('nav_sort_order');

        $magazines = $query->orderBy('name')->get();

        $pinnedMagazines = $magazines->filter(fn (Magazine $magazine) => $magazine->isNavPinned())->values();
        $unpinnedMagazines = $magazines->reject(fn (Magazine $magazine) => $magazine->isNavPinned())->values();

        return view('admin.magazines.index', [
            'magazines' => $magazines,
            'pinnedMagazines' => $pinnedMagazines,
            'unpinnedMagazines' => $unpinnedMagazines,
            'navLimit' => MagazineNavSettings::limit(),
            'navMode' => MagazineNavSettings::mode(),
            'navPinningEnabled' => true,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'magazines_nav_limit' => ['required', 'integer', 'min:1', 'max:50'],
            'magazines_nav_mode' => ['required', 'in:manual,auto'],
        ]);

        MagazineNavSettings::save(
            (int) $data['magazines_nav_limit'],
            $data['magazines_nav_mode'],
        );

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        return back()->with('status', 'magazine-nav-settings-updated');
    }

    public function updateNav(Request $request, Magazine $magazine): RedirectResponse
    {
        $data = $request->validate([
            'pinned' => ['required', 'boolean'],
            'nav_sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $pinned = $request->boolean('pinned');

        $magazine->update([
            'nav_pinned_at' => $pinned ? ($magazine->nav_pinned_at ?? now()) : null,
            'nav_sort_order' => $pinned
                ? (int) ($data['nav_sort_order'] ?? $magazine->nav_sort_order ?? $this->nextNavSortOrder())
                : (int) ($magazine->nav_sort_order ?? 0),
        ]);

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        return back()->with('status', 'magazine-nav-updated');
    }

    public function reorderNav(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'magazine_ids' => ['required', 'array', 'min:1'],
            'magazine_ids.*' => ['integer', 'exists:magazines,id'],
        ]);

        $pinnedIds = Magazine::query()
            ->whereNotNull('nav_pinned_at')
            ->orderBy('nav_sort_order')
            ->pluck('id')
            ->all();

        $submittedIds = array_map(fn ($id) => (int) $id, $data['magazine_ids']);

        if (
            count($submittedIds) !== count($pinnedIds)
            || array_diff($submittedIds, $pinnedIds) !== []
        ) {
            return back()->withErrors([
                'magazine_ids' => __('app.admin_magazine_nav_reorder_invalid'),
            ]);
        }

        foreach ($data['magazine_ids'] as $order => $id) {
            Magazine::query()->whereKey($id)->update(['nav_sort_order' => $order]);
        }

        EntityCache::flushTag(config('entity-cache.tags.magazines'));

        return back()->with('status', 'magazine-nav-reordered');
    }

    private function nextNavSortOrder(): int
    {
        $max = Magazine::query()->whereNotNull('nav_pinned_at')->max('nav_sort_order');

        return ($max === null ? -1 : (int) $max) + 1;
    }
}
