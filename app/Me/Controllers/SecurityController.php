<?php

declare(strict_types=1);

namespace App\Me\Controllers;

use App\Http\Controllers\AuthenticatedController;
use App\Me\Requests\UpdateSecurityRequest;
use App\Me\Services\SecurityService;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class SecurityController extends AuthenticatedController
{
    public function __construct(
        private readonly SecurityService $securityService,
    ) {
        parent::__construct();
    }

    #[ScrambleResponse(200, 'Updated', examples: [['status' => 'updated']])]
    public function update(UpdateSecurityRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['email'])) {
            $this->securityService->updateEmail($this->user, (string) $data['email']);
        }

        if (isset($data['password']) && (string) $data['password'] !== '' && isset($data['current_password'])) {
            try {
                $this->securityService->updatePassword(
                    $this->user,
                    (string) $data['current_password'],
                    (string) $data['password'],
                );
            } catch (\InvalidArgumentException) {
                throw ValidationException::withMessages([
                    'current_password' => [__('The current password is incorrect.')],
                ]);
            }
        }

        return response()->json(['status' => 'updated'], Response::HTTP_OK);
    }
}
