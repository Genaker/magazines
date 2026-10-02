<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserBlockController extends Controller
{
    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 422);

        $blocker = $request->user();

        if ($blocker->hasBlocked($user)) {
            $blocker->blockedUsers()->detach($user->id);

            return back()->with('status', 'user-unblocked');
        }

        $blocker->blockedUsers()->attach($user->id);
        $blocker->following()->detach($user->id);
        $user->following()->detach($blocker->id);
        $blocker->storySubscriptions()->detach($user->id);
        $user->storySubscriptions()->detach($blocker->id);

        return back()->with('status', 'user-blocked');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $request->user()->blockedUsers()->detach($user->id);

        return back()->with('status', 'user-unblocked');
    }
}
