<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's preferred color theme.
     */
    public function updateTheme(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'in:light,dark,system'],
        ]);

        $request->user()->update([
            'theme' => $validated['theme'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['theme' => $validated['theme']]);
        }

        return back();
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
        $userId = $user->id;

        Auth::logout();

        // Exclusão em ordem topológica reversa dentro de transação atômica (LGPD / SQLite FKs)
        DB::transaction(function () use ($user) {
            Receipt::where('user_id', $user->id)->delete();
            Transaction::withTrashed()->where('user_id', $user->id)->forceDelete();
            Appointment::withTrashed()->where('user_id', $user->id)->forceDelete();
            TransactionCategory::where('user_id', $user->id)->delete();
            Client::withTrashed()->where('user_id', $user->id)->forceDelete();

            if ($user->businessProfile) {
                if ($user->businessProfile->logo_path) {
                    Storage::disk('local')->delete($user->businessProfile->logo_path);
                }
                $user->businessProfile()->delete();
            }

            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('audit.account.deleted', [
            'user_id' => $userId,
            'ip' => $request->ip(),
        ]);

        return Redirect::to('/');
    }
}
