<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function create(): View
    {
        return view('auth.password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->update([
            'password' => $request->password,
            'must_change_password' => false,
        ]);

        return $user->isClientViewer()
            ? redirect()->route('portal.dashboard')->with('success', 'Password updated. Welcome to your portal.')
            : redirect()->route('dashboard')->with('success', 'Password updated successfully.');
    }
}
