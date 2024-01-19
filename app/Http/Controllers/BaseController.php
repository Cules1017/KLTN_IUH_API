<?php

namespace  App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    use ApiResponseTrait;

    const INVALID_PARAMETER = 422;
    const PAGINATION_ITEM_PER_PAGE = 50;

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $message)
    {
        return $this->sendOkResponse([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * env('JWT_TTL', 60)
        ], $message);
    }

    /**
     * Override validate method use dingo validation exception
     *
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     */
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = $this->getValidationFactory()
            ->make(
                $request->all(),
                $rules, $messages,
                $customAttributes
            );
        if ($validator->fails()) {
            throw new ValidationException($validator, $this->sendFailedResponse(
                $validator->errors()->first(),
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                [],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()
            ));
        }

        return $validator->validated();
    }
}
