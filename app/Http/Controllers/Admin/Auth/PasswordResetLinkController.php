<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email:filter', 'max:180'],
        ]);

        Password::sendResetLink($request->only('email'));

        // The same message is shown whether or not the address exists, so the
        // form cannot be used to discover staff email addresses.
        return back()->with('status', 'If that address belongs to an account, a reset link is on its way.');
    }
}
