<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessSettingRequest;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BusinessSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin']);
    }

    public function edit(): View
    {
        $settings = BusinessSetting::current();

        return view('accounting.settings.edit', compact('settings'));
    }

    public function update(UpdateBusinessSettingRequest $request): RedirectResponse
    {
        $settings = BusinessSetting::current();
        $settings->fill($request->validated())->save();

        AuditLog::record('updated', 'BusinessSetting', $settings->id, 'Updated business banking details');

        return redirect()
            ->route('accounting.settings.edit')
            ->with('success', 'Business settings saved.');
    }
}
