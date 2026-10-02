<?php

namespace App\Http\Controllers;

use App\Enums\MagazineJoinRequestStatus;
use App\Enums\MagazineMemberRole;
use App\Models\Magazine;
use App\Models\MagazineJoinRequest;
use App\Models\User;
use App\Services\ActivityNotifier;
use App\Services\MagazineNotifier;
use App\Support\Message;
use App\Support\MagazineSubdomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MagazineJoinRequestController extends Controller
{
    /** GET bookmark/history fallback — join requests are submitted via POST only. */
    public function create(Magazine $magazine): RedirectResponse
    {
        return redirect(MagazineSubdomain::canonicalMagazineUrl($magazine));
    }

    public function store(Request $request, Magazine $magazine): RedirectResponse
    {
        $redirect = redirect(MagazineSubdomain::canonicalMagazineUrl($magazine));

        if ($magazine->memberRole($request->user()) !== null) {
            return Message::redirectWith($redirect, Message::TYPE_WARNING, __('message.status.magazine-join-already-member'));
        }

        $pendingExists = MagazineJoinRequest::query()
            ->where('magazine_id', $magazine->id)
            ->where('user_id', $request->user()->id)
            ->where('status', MagazineJoinRequestStatus::Pending)
            ->exists();

        if ($pendingExists) {
            return Message::redirectWith($redirect, Message::TYPE_WARNING, __('message.status.magazine-join-already-pending'));
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $joinRequest = MagazineJoinRequest::query()->create([
            'magazine_id' => $magazine->id,
            'user_id' => $request->user()->id,
            'message' => $data['message'] ?? null,
            'status' => MagazineJoinRequestStatus::Pending,
        ]);

        $magazine->load('owner');
        ActivityNotifier::notify($magazine->owner, $request->user(), 'magazine_join_request', magazine: $magazine);

        $magazine->members()
            ->wherePivot('role', MagazineMemberRole::Editor->value)
            ->each(fn (User $editor) => ActivityNotifier::notify($editor, $request->user(), 'magazine_join_request', magazine: $magazine));

        MagazineNotifier::joinRequestSubmitted($joinRequest);

        return Message::redirectWith($redirect, Message::TYPE_SUCCESS, __('message.status.magazine-join-requested'));
    }

    public function approve(Request $request, Magazine $magazine, MagazineJoinRequest $joinRequest): RedirectResponse
    {
        $this->authorize('reviewJoinRequests', $magazine);
        abort_unless($joinRequest->magazine_id === $magazine->id, 404);
        abort_unless($joinRequest->isPending(), 422);

        $joinRequest->update([
            'status' => MagazineJoinRequestStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $magazine->members()->syncWithoutDetaching([
            $joinRequest->user_id => ['role' => MagazineMemberRole::Writer->value],
        ]);

        ActivityNotifier::notify($joinRequest->user, $request->user(), 'magazine_join_approved', magazine: $magazine);

        MagazineNotifier::joinRequestApproved($joinRequest, $magazine);

        return back()->with('status', 'magazine-join-approved');
    }

    public function reject(Request $request, Magazine $magazine, MagazineJoinRequest $joinRequest): RedirectResponse
    {
        $this->authorize('reviewJoinRequests', $magazine);
        abort_unless($joinRequest->magazine_id === $magazine->id, 404);
        abort_unless($joinRequest->isPending(), 422);

        $joinRequest->update([
            'status' => MagazineJoinRequestStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        MagazineNotifier::joinRequestRejected($joinRequest, $magazine);

        return back()->with('status', 'magazine-join-rejected');
    }
}
