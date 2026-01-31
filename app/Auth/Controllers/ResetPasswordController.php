<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Actions\ResetPasswordAction;
use App\Auth\Requests\ResetPasswordRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly ResetPasswordAction $action,
    ) {}

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $response = response()->json();
        $userDto = $this->action->execute(
            (string) $data['token'],
            (string) $data['password'],
            $response,
        );

        if ($userDto === null) {
            return response()->json(['message' => 'Invalid or expired reset token'], 400);
        }

        return $response->setData(['user' => $userDto->toArray()]);
    }
}
