<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\AuthorSocialLinks;
use App\Support\AvatarUploadProcessor;
use App\Support\CustomFieldSchema;
use App\Support\CustomFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load('blockedUsers');

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, AvatarUploadProcessor $avatars): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except(['avatar', 'allow_comments', 'custom_fields', 'social_links']));
        $user->allow_comments = $request->boolean('allow_comments');
        $user->custom_fields = CustomFields::collect($request->input('custom_fields'), CustomFieldSchema::CONTEXT_USER);
        $user->social_links = AuthorSocialLinks::normalize($request->input('social_links'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = $avatars->store($request->file('avatar'));
        }

        $user->save();

        $primaryAlias = $user->primaryAlias();
        $primaryAlias->update([
            'name' => $user->name,
            'username' => $user->username,
            'bio' => $user->bio,
            'avatar' => $user->avatar,
            'website' => $user->website,
            'twitter_handle' => $user->twitter_handle,
            'social_links' => $user->social_links,
        ]);

        return Redirect::back(302, [], route('profile.edit'))->with('status', 'profile-updated');
    }

    /** Remove the uploaded avatar and revert to generated initials. */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();

            $user->primaryAlias()?->update(['avatar' => null]);
        }

        return Redirect::back()->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasPassword()) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
