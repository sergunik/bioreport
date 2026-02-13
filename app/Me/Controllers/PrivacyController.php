<?php

declare(strict_types=1);

namespace App\Me\Controllers;

use App\Auth\Services\CookieService;
use App\Http\Controllers\AuthenticatedController;
use App\Me\Requests\DeleteMeRequest;
use App\Me\Services\PrivacyService;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

final class PrivacyController extends AuthenticatedController
{
    public function __construct(
        private readonly PrivacyService $privacyService,
        private readonly CookieService $cookieService,
    ) {
        parent::__construct();
    }

    #[ScrambleResponse(204, 'User deleted')]
    public function destroy(DeleteMeRequest $request): Response
    {
        $password = (string) $request->validated()['password'];

        try {
            $this->privacyService->deleteUserWithPasswordConfirmation($this->user, $password);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'password' => [__('The password is incorrect.')],
            ]);
        }

        $response = response()->noContent();
        $this->cookieService->clearAuthCookies($response);

        return $response;
    }
}
