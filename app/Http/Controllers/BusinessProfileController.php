<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessProfileUpdateRequest;
use App\Models\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
            'profile' => $profile,
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

        $profile->update($request->validated());

        return back()->with('status', 'profile-updated');
    }
}
