<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $showSuccess = $request->user()->hasVerifiedEmail()
            && ($request->boolean('verified') || $request->session()->get('status') === 'email-verified');

        if ($request->user()->hasVerifiedEmail() && ! $showSuccess) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return view('auth.verify-email', [
            'email' => $request->user()->user_email,
            'verified' => $showSuccess,
        ]);
    }
}
