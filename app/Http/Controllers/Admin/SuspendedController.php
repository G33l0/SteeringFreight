<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * What a paused or expired account sees instead of the panel.
 *
 * The wording is a site setting rather than template copy, so an administrator
 * can say who to contact and on what terms without an edit to the code. Nothing
 * on this screen needs a permission: a suspended account has none.
 */
class SuspendedController extends Controller
{
    public function __construct(private readonly Settings $settings) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isSuspended()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.suspended', [
            'staff' => $user,
            'notice' => $this->settings->string(
                'security.renewal_note',
                'Your access to the panel has been paused. Contact the administrator to have it restored.',
            ),
            'contactEmail' => $this->settings->string('contact.email') ?: null,
        ]);
    }
}
