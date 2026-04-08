<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePartnerPreferenceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class PartnerPreferenceController extends Controller
{
    public function edit(Request $request): View
    {
        $preference = $request->user()->partnerPreference;

        return view('preferences.edit', compact('preference'));
    }

    public function update(UpdatePartnerPreferenceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach (
            [
                'must_have_job',
                'must_be_calm',
                'must_love_children',
                'must_be_modest',
                'must_be_respectful',
            ] as $flag
        ) {
            $validated[$flag] = (bool) ($validated[$flag] ?? false);
        }

        $request->user()->partnerPreference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return Redirect::route('preferences.edit')->with('status', 'preferences-saved');
    }
}
