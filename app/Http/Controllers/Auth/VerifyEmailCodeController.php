<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailCodeController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified using a code.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        if ($user->email_verification_code !== $request->code) {
            return back()->withErrors(['code' => 'The provided verification code is incorrect.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $user->email_verification_code = null;
            $user->save();
        }

        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
    }
}
