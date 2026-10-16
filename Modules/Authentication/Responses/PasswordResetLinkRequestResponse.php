<?php

declare(strict_types=1);

namespace Modules\Authentication\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

/**
 * Gives the same answer whether or not an account exists for the submitted email (and whether
 * the request was throttled), so the "Forgot password" form cannot be used to discover which
 * email addresses have accounts.
 */
class PasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public const MESSAGE = 'If an account matches that email address, a password reset link has been sent to it. Please check your inbox.';

    public function __construct(protected string $status = '') {}

    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => self::MESSAGE], 200);
        }

        return back()->with('status', self::MESSAGE);
    }
}
