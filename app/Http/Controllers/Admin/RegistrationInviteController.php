<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrationInviteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $code = $this->generateUniqueCode();

        RegistrationInvite::query()->create([
            'code' => $code,
            'created_by' => $request->user()->id,
            'max_uses' => $data['max_uses'] ?? 1,
            'expires_at' => isset($data['expires_in_days'])
                ? now()->addDays((int) $data['expires_in_days'])
                : null,
        ]);

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'invite-created')
            ->with('invite_code', $code);
    }

    public function destroy(RegistrationInvite $invite): RedirectResponse
    {
        $invite->update(['revoked_at' => now()]);

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'invite-revoked');
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (RegistrationInvite::query()->where('code', $code)->exists());

        return $code;
    }
}
