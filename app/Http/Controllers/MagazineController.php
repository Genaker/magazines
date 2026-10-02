<?php

namespace App\Http\Controllers;

use App\Enums\MagazineMemberRole;
use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Enums\MagazineJoinRequestStatus;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\MagazineJoinRequest;
use App\Models\Post;
use App\Models\User;
use App\Services\ImageService;
use App\Services\MagazineNotifier;
use App\Support\DatabaseFullTextSearch;
use App\Support\MagazinePostSubmission;
use App\Support\Seo;
use App\Support\SubdomainLabel;
use App\Support\CustomFieldSchema;
use App\Support\CustomFields;
use App\Support\Slugger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MagazineController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->get('q', ''));

        $magazines = Magazine::query()
            ->withCount(['posts' => fn ($q) => $q->where('status', PostStatus::Published)
                ->where('magazine_submission_status', MagazineSubmissionStatus::Approved)])
            ->when($query !== '', fn ($builder) => $builder->where(function ($inner) use ($query): void {
                DatabaseFullTextSearch::matchAny($inner, ['name', 'description', 'slug'], $query);
            }))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('magazines.index', compact('magazines', 'query'));
    }

    public function show(Magazine $magazine, Request $request): View
    {
        $categories = Category::cachedForMagazine($magazine->id);
        $activeCategory = null;

        if ($request->filled('category')) {
            $activeCategory = Category::query()
                ->where('slug', $request->query('category'))
                ->where('magazine_id', $magazine->id)
                ->first();
        }

        $postsQuery = $magazine->posts()
            ->with(['user', 'category', 'tags'])
            ->where('status', PostStatus::Published)
            ->where('magazine_submission_status', MagazineSubmissionStatus::Approved);

        if ($activeCategory) {
            $postsQuery->whereIn('category_id', Category::descendantIdsFor($activeCategory->id));
        }

        $posts = $postsQuery
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        $viewer = auth()->user();

        return view('magazines.show', [
            'magazine' => $magazine,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'coverUrl' => $magazine->coverImageUrl(),
            'posts' => $posts,
            'seo' => Seo::forMagazine($magazine),
            'isFollowing' => $viewer && $viewer->followedMagazines()->where('magazine_id', $magazine->id)->exists(),
            'isMember' => $viewer && $magazine->memberRole($viewer) !== null,
            'hasPendingJoinRequest' => $viewer && MagazineJoinRequest::query()
                ->where('magazine_id', $magazine->id)
                ->where('user_id', $viewer->id)
                ->where('status', MagazineJoinRequestStatus::Pending)
                ->exists(),
            'followersCount' => $magazine->followers()->count(),
        ]);
    }

    public function mine(Request $request): View
    {
        $user = $request->user();

        $magazines = Magazine::query()
            ->where(function ($query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($memberQuery) => $memberQuery->where('user_id', $user->id));
            })
            ->withCount(['posts' => fn ($q) => $q->where('status', PostStatus::Published)
                ->where('magazine_submission_status', MagazineSubmissionStatus::Approved)])
            ->orderBy('name')
            ->get();

        return view('magazines.mine', compact('magazines'));
    }

    public function create(): View
    {
        $this->authorize('create', Magazine::class);

        return view('magazines.create');
    }

    public function store(Request $request, ImageService $images): RedirectResponse
    {
        $this->authorize('create', Magazine::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:'.config('media.max_upload_kb')],
            ...CustomFields::validationRules(CustomFieldSchema::CONTEXT_MAGAZINE),
        ]);

        $magazine = Magazine::query()->create([
            'owner_id' => $request->user()->id,
            'name' => $data['name'],
            'slug' => Slugger::unique($data['name'], new Magazine, 'slug'),
            'description' => $data['description'] ?? null,
            'custom_fields' => CustomFields::collect($request->input('custom_fields'), CustomFieldSchema::CONTEXT_MAGAZINE),
        ]);

        $magazine->members()->attach($request->user()->id, [
            'role' => MagazineMemberRole::Owner->value,
        ]);

        if ($request->hasFile('logo')) {
            $processed = $images->processUpload($request->file('logo'), 'magazines');
            $magazine->update([
                'logo' => $processed['path'],
                'logo_variants' => $processed['variants'],
            ]);
        }

        return redirect()->route('magazines.show', $magazine);
    }

    public function submissions(Request $request, Magazine $magazine): View
    {
        $this->authorize('reviewSubmissions', $magazine);

        $submissions = $magazine->posts()
            ->with(['user', 'category'])
            ->where('magazine_submission_status', MagazineSubmissionStatus::Pending)
            ->orderByDesc('updated_at')
            ->paginate(15);

        $joinRequests = MagazineJoinRequest::query()
            ->with('user')
            ->where('magazine_id', $magazine->id)
            ->where('status', MagazineJoinRequestStatus::Pending)
            ->latest()
            ->get();

        $roleOrder = ['owner' => 0, 'editor' => 1, 'writer' => 2];
        $magazine->load(['owner', 'members']);
        $members = $magazine->members
            ->sortBy(fn (User $user) => [
                $roleOrder[$user->pivot->role] ?? 99,
                strtolower($user->name),
            ])
            ->values();

        if (! $members->contains('id', $magazine->owner_id)) {
            $members->prepend($magazine->owner);
        }

        return view('magazines.submissions', compact('magazine', 'submissions', 'joinRequests', 'members'));
    }

    public function approveSubmission(Request $request, Magazine $magazine, Post $post): RedirectResponse
    {
        $this->authorize('reviewSubmissions', $magazine);
        abort_unless($post->magazine_id === $magazine->id, 404);

        $post->update([
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            'status' => PostStatus::Published,
            'published_at' => $post->published_at ?? now(),
        ]);

        MagazineNotifier::postApproved($post->fresh(['user', 'magazine', 'authorAlias']));

        return back()->with('status', 'submission-approved');
    }

    public function rejectSubmission(Request $request, Magazine $magazine, Post $post): RedirectResponse
    {
        $this->authorize('reviewSubmissions', $magazine);
        abort_unless($post->magazine_id === $magazine->id, 404);

        $post->update([
            'magazine_submission_status' => MagazineSubmissionStatus::Rejected,
        ]);

        MagazineNotifier::postRejected($post->fresh(['user', 'magazine']));

        return back()->with('status', 'submission-rejected');
    }

    public function inviteMember(Request $request, Magazine $magazine): RedirectResponse
    {
        $this->authorize('update', $magazine);

        $request->merge([
            'username' => SubdomainLabel::forNickname((string) $request->input('username', '')),
        ]);

        $data = $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
            'role' => ['required', 'in:editor,writer'],
        ]);

        $user = User::query()->where('username', $data['username'])->firstOrFail();

        if ($user->id === $magazine->owner_id) {
            return back()->withErrors(['username' => 'Owner is already a member.']);
        }

        $magazine->members()->syncWithoutDetaching([
            $user->id => ['role' => $data['role']],
        ]);

        return back()->with('status', 'member-invited');
    }

    public function submitPost(Request $request, Magazine $magazine, Post $post): RedirectResponse
    {
        $this->authorize('submit', $magazine);
        $this->authorize('update', $post);
        abort_unless($post->user_id === $request->user()->id, 403);

        $post->update([
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazinePostSubmission::statusOnSave(
                $request->user(),
                $magazine,
                PostStatus::Draft,
            ) ?? MagazineSubmissionStatus::Pending,
            'status' => PostStatus::Draft,
        ]);

        MagazineNotifier::postSubmitted($post->fresh(['user', 'magazine']));

        return back()->with('status', 'submitted-to-magazine');
    }

    public function updateSubmissionSettings(Request $request, Magazine $magazine): RedirectResponse
    {
        $this->authorize('reviewSubmissions', $magazine);

        $request->validate([
            'require_post_approval' => ['sometimes', 'boolean'],
        ]);

        $magazine->update([
            'require_post_approval' => $request->boolean('require_post_approval'),
        ]);

        return back()->with('status', 'magazine-settings-updated');
    }
}
