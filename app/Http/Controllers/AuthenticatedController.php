<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base controller that requires an authenticated JWT user.
 */
abstract class AuthenticatedController extends Controller
{
    protected User $user;

    /**
     * Loads the current JWT user or aborts with 401.
     */
    public function __construct()
    {
        $user = Auth::guard('jwt')->user();
        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $this->user = $user;
    }
}
