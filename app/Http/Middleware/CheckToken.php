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

class CheckToken
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
            $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token)->toArray();
       
        if($apy['exp']<=strtotime(now())){
            return $this->sendFailedResponse("Đã quá hạn phiên đăng nhập vui lòng đăng nhập lại.",JsonResponse::HTTP_UNAUTHORIZED,  [], JsonResponse::HTTP_UNAUTHORIZED, null);
        }
        global $user_info;
        $user_info=auth($apy['user_type'])->user();
        $user_info['user_type']=$apy['user_type'];

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            # token_expired
            return $this->sendFailedResponse($e->getMessage(),JsonResponse::HTTP_UNAUTHORIZED,  [], JsonResponse::HTTP_UNAUTHORIZED, null);
    
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            # token_invalid
            return $this->sendFailedResponse($e->getMessage(), JsonResponse::HTTP_UNAUTHORIZED,  [], JsonResponse::HTTP_UNAUTHORIZED, null);
    
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            # token absent
            return $this->sendFailedResponse($e->getMessage(), JsonResponse::HTTP_UNAUTHORIZED,  [], JsonResponse::HTTP_UNAUTHORIZED, null);

        } catch (Throwable $e) {
            return $this->sendFailedResponse('Please ensure you have entered the token and that your token is valid.',JsonResponse::HTTP_UNAUTHORIZED,  [],JsonResponse::HTTP_UNAUTHORIZED, null);
        }
    
        return $next($request);
    }
}