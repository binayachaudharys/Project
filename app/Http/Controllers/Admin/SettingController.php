<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Repositories\SettingRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(SettingRepository $settings): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings->allResolved(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, SettingRepository $settings): RedirectResponse
    {
        $settings->updateMany($request->validated());

        return redirect()->route('admin.settings.edit')->with('success', 'Settings saved.');
    }
}
