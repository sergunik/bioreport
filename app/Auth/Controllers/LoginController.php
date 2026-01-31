<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Actions\LoginUserAction;
use App\Auth\DTOs\CredentialsDto;
use App\Auth\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class LoginController extends Controller
{
    public function __construct(
        private readonly LoginUserAction $action,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $response = response()->json();
        $userDto = $this->action->execute(
            CredentialsDto::fromValidated($request->validated()),
            $response,
        );

        if ($userDto === null) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return $response->setData(['user' => $userDto->toArray()]);
    }
}
