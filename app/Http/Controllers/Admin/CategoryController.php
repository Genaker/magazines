<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryRedirect;
use App\Models\Magazine;
use App\Services\CategoryTaxonomyService;
use App\Services\ImageService;
use App\Support\Features;
use App\Support\ModeratorAssigner;
use App\Support\Slugger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private CategoryTaxonomyService $taxonomy) {}

    public function index(): View
    {
        $siteCategories = Category::buildTree(
            Category::query()
                ->whereNull('magazine_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        );

        $magazineCategoryGroups = Magazine::query()
            ->whereHas('categories')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Magazine $magazine) => [
                $magazine->id => [
                    'magazine' => $magazine,
                    'categories' => Category::buildTree(
                        $magazine->categories()->get(),
                    ),
                ],
            ]);

        return view('admin.categories.index', compact('siteCategories', 'magazineCategoryGroups'));
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'magazines' => $this->magazineOptions(),
            'selectedMagazineId' => old('magazine_id') ? (int) old('magazine_id') : null,
            'selectedParentId' => old('parent_id') ? (int) old('parent_id') : null,
        ]);
    }

    public function parentOptions(Request $request): JsonResponse
    {
        $magazineId = $request->filled('magazine_id') ? (int) $request->query('magazine_id') : null;
        $exclude = $request->filled('exclude')
            ? Category::query()->find((int) $request->query('exclude'))
            : null;

        $options = Category::validParentOptions($exclude, $magazineId);

        return response()->json([
            'options' => $options->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'depth' => $category->depth ?? 0,
            ])->values(),
        ]);
    }

    public function store(Request $request, ImageService $images): RedirectResponse
    {
        $data = $this->validatedCategoryData($request);

        $category = Category::query()->create([
            'name' => $data['name'],
            'slug' => Slugger::unique($data['name'], new Category, 'slug'),
            'parent_id' => $data['parent_id'] ?? null,
            'magazine_id' => $data['magazine_id'] ?? null,
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->storeCategoryImage($category, $request, $images);

        return redirect()->route('admin.categories.index');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'magazines' => $this->magazineOptions(),
            'selectedMagazineId' => old('magazine_id', $category->magazine_id),
            'selectedParentId' => old('parent_id', $category->parent_id),
            'excludeCategoryId' => $category->id,
            'postCount' => $category->posts()->count(),
            'moderatorEmails' => $category->magazine_id === null
                ? implode("\n", ModeratorAssigner::emailsFor($category))
                : '',
            'mergeTargets' => Category::query()
                ->whereKeyNot($category->id)
                ->where('magazine_id', $category->magazine_id)
                ->orderBy('name')
                ->get(),
            'otherCategories' => Category::query()
                ->whereKeyNot($category->id)
                ->where('magazine_id', $category->magazine_id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Category $category, ImageService $images): RedirectResponse
    {
        $data = $this->validatedCategoryData($request, $category);

        $slug = $category->slug;

        if ($data['name'] !== $category->name) {
            $candidate = Slugger::unique($data['name'], new Category, 'slug', $category->id);

            if ($candidate !== $category->slug) {
                CategoryRedirect::register($category->slug, $category);
                $slug = $candidate;
            }
        }

        $category->update([
            'name' => $data['name'],
            'slug' => $slug,
            'parent_id' => $data['parent_id'] ?? null,
            'magazine_id' => $data['magazine_id'] ?? null,
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->storeCategoryImage($category, $request, $images);

        if ($category->magazine_id === null && array_key_exists('moderator_emails', $data)) {
            $emails = preg_split('/[\s,]+/', (string) $data['moderator_emails'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
            ModeratorAssigner::syncFromEmails($category, $emails);
        }

        return redirect()->route('admin.categories.index');
    }

    public function merge(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'merge_into_id' => ['required', 'exists:categories,id', 'not_in:'.$category->id],
        ]);

        $into = Category::query()->findOrFail($data['merge_into_id']);

        try {
            $this->taxonomy->merge($category, $into);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category merged.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $fallback = null;

        if ($category->posts()->exists()) {
            $otherCategoryIds = Category::query()
                ->whereKeyNot($category->id)
                ->where('magazine_id', $category->magazine_id)
                ->pluck('id');

            if ($otherCategoryIds->isEmpty()) {
                return back()->withErrors([
                    'fallback_category_id' => 'Create another category before deleting one that has posts.',
                ]);
            }

            $data = $request->validate([
                'fallback_category_id' => ['required', 'exists:categories,id', 'not_in:'.$category->id],
            ]);

            $fallback = Category::query()->findOrFail($data['fallback_category_id']);

            if ($fallback->magazine_id !== $category->magazine_id) {
                return back()->withErrors([
                    'fallback_category_id' => 'Fallback category must belong to the same magazine scope.',
                ]);
            }
        }

        try {
            $this->taxonomy->delete($category, $fallback);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('admin.categories.index');
    }

    /** @return array<string, mixed> */
    private function validatedCategoryData(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'magazine_id' => ['nullable', 'exists:magazines,id'],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn(array_filter([$category?->id])),
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $category): void {
                    if (! $value) {
                        return;
                    }

                    $parent = Category::query()->find($value);
                    $magazineId = $request->input('magazine_id') ?: null;

                    if (! $parent || ($parent->magazine_id ?? null) != ($magazineId ?: null)) {
                        $fail('Parent category must belong to the same magazine scope.');

                        return;
                    }

                    if ($category && Category::isDescendantOf($parent, $category)) {
                        $fail('A category cannot be nested under its own subcategory.');
                    }
                },
            ],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:'.config('media.max_upload_kb')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'moderator_emails' => ['nullable', 'string'],
        ]);

        $data['magazine_id'] = filled($data['magazine_id'] ?? null) ? (int) $data['magazine_id'] : null;

        return $data;
    }

    private function magazineOptions()
    {
        if (! Features::enabled('magazines')) {
            return collect();
        }

        return Magazine::query()->orderBy('name')->get(['id', 'name']);
    }

    private function storeCategoryImage(Category $category, Request $request, ImageService $images): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $images->deleteVariants($category->image_variants);

        $processed = $images->processUpload($request->file('image'), 'categories');

        $category->update([
            'image' => $processed['path'],
            'image_variants' => $processed['variants'],
        ]);
    }
}
