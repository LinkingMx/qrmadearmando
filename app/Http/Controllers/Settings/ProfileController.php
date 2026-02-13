<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Laravel\Facades\Image;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Handle avatar removal
        if ($request->boolean('remove_avatar')) {
            $this->deleteOldAvatar($user->avatar);
            $user->avatar = null;
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $this->deleteOldAvatar($user->avatar);

            $filename = Str::slug($user->email).'_'.Str::random(8).'.webp';

            $optimized = Image::read($request->file('avatar'))
                ->coverDown(400, 400)
                ->toWebp(quality: 80);

            Storage::disk('public')->put("avatars/{$filename}", (string) $optimized);

            $user->avatar = "avatars/{$filename}";
        }

        $user->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Delete the old avatar file from storage.
     */
    private function deleteOldAvatar(?string $avatarPath): void
    {
        if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
            Storage::disk('public')->delete($avatarPath);
        }
    }
}
