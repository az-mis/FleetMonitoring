<?php

declare(strict_types=1);

namespace Modules\Authentication\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\PasswordUpdateResponse as PasswordUpdateResponseContract;

/**
 * After a signed-in user changes their own password (My Profile → Change Password), returns to the
 * page with the app's usual success toast instead of Fortify's bare "password-updated" status.
 */
class PasswordUpdateResponse implements PasswordUpdateResponseContract
{
    public const MESSAGE = 'Your password has been changed.';

    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => self::MESSAGE], 200);
        }

        return back()->with('success', self::MESSAGE);
    }
}
