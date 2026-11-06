<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessProfileUpdateRequest;
use App\Models\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $profile = $request->user()->businessProfile()->firstOrCreate([
            'user_id' => $request->user()->id,
        ], [
            'display_name' => $request->user()->name,
            'store_name' => $request->user()->name,
        ]);

        return Inertia::render('BusinessProfile/Edit', [
            'profile' => [
                ...$profile->toArray(),
                'logo_url' => $profile->logo_path ? route('business-profile.logo') : null,
            ],
            'status' => session('status'),
        ]);
    }

    public function update(BusinessProfileUpdateRequest $request): RedirectResponse
    {
        $profile = $request->user()->businessProfile()->firstOrCreate([
            'user_id' => $request->user()->id,
        ], [
            'display_name' => $request->user()->name,
        ]);

        $data = $request->safe()->except(['logo']);

        if ($request->hasFile('logo')) {
            // Remove previous logo if exists
            if ($profile->logo_path && Storage::disk('local')->exists($profile->logo_path)) {
                Storage::disk('local')->delete($profile->logo_path);
            }

            $path = $request->file('logo')->store('private/logos', 'local');
            $data['logo_path'] = $path;
        }

        $profile->update($data);

        return back()->with('status', 'profile-updated');
    }

    public function logo(Request $request): BinaryFileResponse
    {
        $profile = $request->user()->businessProfile;

        if (! $profile || ! $profile->logo_path || ! Storage::disk('local')->exists($profile->logo_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($profile->logo_path));
    }
}
