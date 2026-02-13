<?php

declare(strict_types=1);

namespace App\Me\Controllers;

use App\Account\Services\AccountService;
use App\Account\Services\AccountServiceFactory;
use App\Http\Controllers\AuthenticatedController;
use App\Me\DTOs\MeDto;
use App\Me\Requests\UpdateProfileRequest;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ProfileController extends AuthenticatedController
{
    private readonly AccountService $accountService;

    public function __construct(AccountServiceFactory $accountServiceFactory)
    {
        parent::__construct();
        $this->accountService = $accountServiceFactory->make($this->user);
    }

    #[ScrambleResponse(200, 'Current profile', examples: [['id' => '1', 'email' => 'user@example.com', 'nickname' => 'John', 'date_of_birth' => '1990-01-15', 'sex' => 'male', 'language' => 'uk', 'timezone' => 'Europe/Kyiv']])]
    public function show(): JsonResponse
    {
        $account = $this->accountService->getOrFail();

        return response()->json(MeDto::fromUserAndAccount($this->user, $account)->toArray());
    }

    #[ScrambleResponse(200, 'Updated', examples: [['status' => 'updated']])]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $this->accountService->update($request->validated());

        return response()->json(['status' => 'updated'], Response::HTTP_OK);
    }
}
