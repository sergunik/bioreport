<?php

declare(strict_types=1);

namespace App\Account\Controllers;

use App\Account\DTOs\AccountCreateDto;
use App\Account\DTOs\AccountDto;
use App\Account\Requests\CreateAccountRequest;
use App\Account\Requests\UpdateAccountRequest;
use App\Account\Services\AccountService;
use App\Account\Services\AccountServiceFactory;
use App\Auth\Services\CookieService;
use App\Http\Controllers\AuthenticatedController;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AccountController extends AuthenticatedController
{
    private readonly AccountService $accountService;

    /**
     * Initializes the controller with the current user.
     */
    public function __construct(
        AccountServiceFactory $accountServiceFactory,
        private readonly CookieService $cookieService,
    ) {
        parent::__construct();

        $this->accountService = $accountServiceFactory->make($this->user);
    }

    /**
     * Creates an account for the current user.
     */
    #[ScrambleResponse(201, 'Created account', examples: [['id' => '1', 'nickname' => null, 'date_of_birth' => '1990-01-15', 'sex' => 'male', 'language' => 'uk', 'timezone' => 'Europe/Kyiv', 'sensitive_words' => null]])]
    public function store(CreateAccountRequest $request): JsonResponse
    {
        $existing = $this->accountService->getOrNull();

        if ($existing !== null) {
            return response()->json([
                'status' => 'account_exists',
            ], Response::HTTP_CONFLICT);
        }

        $account = $this->accountService->create(
            AccountCreateDto::fromValidated($request->validated())
        );

        return response()->json(AccountDto::fromModel($account)->toArray(), Response::HTTP_CREATED);
    }

    /**
     * Returns the current user account.
     */
    #[ScrambleResponse(200, 'Current account', examples: [['id' => '1', 'nickname' => 'John', 'date_of_birth' => '1990-01-15', 'sex' => 'male', 'language' => 'uk', 'timezone' => 'Europe/Kyiv', 'sensitive_words' => 'patient doctor diagnosis']])]
    public function show(): JsonResponse
    {
        $account = $this->accountService->getOrFail();

        return response()->json(AccountDto::fromModel($account)->toArray());
    }

    /**
     * Updates the current user account.
     */
    #[ScrambleResponse(200, 'Updated', examples: [['status' => 'updated']])]
    public function update(UpdateAccountRequest $request): JsonResponse
    {
        $this->accountService->update($request->validated());

        return response()->json([
            'status' => 'updated',
        ]);
    }

    /**
     * Deletes the current user account.
     */
    #[ScrambleResponse(200, 'Account deleted', examples: [['status' => 'account_deleted']])]
    public function destroy(): JsonResponse
    {
        $response = response()->json([
            'status' => 'account_deleted',
        ]);

        $this->accountService->deleteAccountAndUser();

        $this->cookieService->clearAuthCookies($response);

        return $response;
    }
}
