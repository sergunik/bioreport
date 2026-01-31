<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Actions\RegisterUserAction;
use App\Auth\DTOs\CredentialsDto;
use App\Auth\Requests\RegisterRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class RegisterController extends Controller
{
    public function __construct(
        private readonly RegisterUserAction $action,
    ) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        /** @var JsonResponse $response */
        $response = response()->json([], Response::HTTP_CREATED);
        $userDto = $this->action->execute(
            CredentialsDto::fromValidated($request->validated()),
            $response,
        );

        return $response->setData(['user' => $userDto->toArray()]);
    }
}
