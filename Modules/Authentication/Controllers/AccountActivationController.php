<?php

declare(strict_types=1);

namespace Modules\Authentication\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Authentication\Actions\PasswordValidationRules;
use Modules\Authentication\Support\AccountActivation;

/**
 * The page an activation email opens: the driver creates their own password and is signed in.
 */
class AccountActivationController extends Controller
{
    use PasswordValidationRules;

    public function show(Request $request, User $user): View
    {
        return view('authentication::activate-account', [
            'user' => $user,
            'usable' => $this->usable($request, $user),
        ]);
    }

    public function store(Request $request, User $user): View|RedirectResponse
    {
        if (! $this->usable($request, $user)) {
            return view('authentication::activate-account', ['user' => $user, 'usable' => false]);
        }

        $request->validate(['password' => $this->passwordRules()]);

        // The new password also makes this link (and any older one) stop working; a new remember token ends any
        // "Remember me" sign-in made with the password the admin had set
        $user->forceFill([
            'password' => Hash::make($request->string('password')->value()),
            'remember_token' => Str::random(60),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect(config('fortify.home'))->with('success', "Your account is active. Welcome, {$user->name}!");
    }

    /** Signed, not expired, made for the account's current email and password, and the account is active */
    private function usable(Request $request, User $user): bool
    {
        return $request->hasValidRelativeSignature()
            && hash_equals(AccountActivation::fingerprint($user), (string) $request->query('v'))
            && $user->isActive();
    }
}
