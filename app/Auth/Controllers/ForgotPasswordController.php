<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Actions\ForgotPasswordAction;
use App\Auth\Requests\ForgotPasswordRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly ForgotPasswordAction $action,
    ) {}

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');

        $this->action->execute(is_string($email) ? $email : '');

        return response()->json(['status' => 'ok']);
    }
}
