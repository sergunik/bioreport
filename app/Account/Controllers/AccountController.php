<?php

declare(strict_types=1);

namespace App\Account\Controllers;

use App\Account\DTOs\AccountCreateDto;
use App\Account\DTOs\AccountDto;
use App\Account\Requests\CreateAccountRequest;
use App\Account\Requests\UpdateAccountRequest;
use App\Account\Services\AccountService;
use App\Auth\Services\CookieService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly CookieService $cookieService,
    ) {}

    public function store(CreateAccountRequest $request): JsonResponse
    {
        $user = Auth::guard('jwt')->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $existing = $this->accountService->getForUserOrNull($user);

        if ($existing !== null) {
            return response()->json([
                'status' => 'account_exists',
            ], Response::HTTP_CONFLICT);
        }

        $account = $this->accountService->createForUser(
            $user,
            AccountCreateDto::fromValidated($request->validated())
        );

        return response()->json(AccountDto::fromModel($account)->toArray(), Response::HTTP_CREATED);
    }

    public function show(Request $request): JsonResponse
    {
        $user = Auth::guard('jwt')->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $account = $this->accountService->getForUserOrFail($user);

        return response()->json(AccountDto::fromModel($account)->toArray());
    }

    public function update(UpdateAccountRequest $request): JsonResponse
    {
        $user = Auth::guard('jwt')->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $account = $this->accountService->updateForUser($user, $request->validated());

        return response()->json([
            'status' => 'updated',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = Auth::guard('jwt')->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $response = response()->json([
            'status' => 'account_deleted',
        ]);

        $this->accountService->deleteAccountAndUser($user);

        $this->cookieService->clearAuthCookies($response);

        return $response;
    }
}
