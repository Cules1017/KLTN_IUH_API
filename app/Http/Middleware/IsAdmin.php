<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;
use Illuminate\Http\JsonResponse;
use Throwable;

class IsAdmin
{
    use ApiResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    { 
        //decode token
        
        
        try {
            global $user_info;
            if($user_info->user_type!='admin')
                return $this->sendFailedResponse('Bạn không có quyền truy cập.',JsonResponse::HTTP_FORBIDDEN,  [],JsonResponse::HTTP_FORBIDDEN, null);
        } catch (Throwable $e) {
            return $this->sendFailedResponse('Bạn không có quyền truy cập.',JsonResponse::HTTP_FORBIDDEN,  [],JsonResponse::HTTP_FORBIDDEN, null);
        }
    
        return $next($request);
    }
}