<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleLoggingMiddleware
{
    protected $aUUID;

    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct()
    {
        $uuid = request()->server('SERVER_ADDR') . '-' . substr(Str::uuid(), 0, 10);
        $this->aUUID = json_encode(['uid' => $uuid]);
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            //dd( request()->ip());
            $timeStart = microtime(true);
            $aData = json_encode([]);
            $req = $request->getMethod()
                . ' ' . $request->url().' [IP:'.request()->ip().']'
                . ' ' . http_build_query($request->all())
                . ' ' . http_build_query($request->header())
                . ' ' . $aData
                . ' ' . $this->aUUID;
            // Save log request API
            Log::channel('custom_info')->info($req , []);
            $response = $next($request);
            // Add time exec and ip
            $data = $response->getData();
            $dt = microtime(true) - $timeStart; // thời gian xử lý
            $data->IP_SERVER = $request->server('SERVER_ADDR');
            $data->time_exec = round($dt * 1000);
            $data->ser_time = date('d-m-Y H:i:s');
            $data->ip_client = $request->ip();
            $res = $response->getStatusCode()
                . ' ' . $response->statusText()
                . ' ' . json_encode($data)
                . ' ' . $aData
                . ' ' . $this->aUUID;
            // Save log response API
            Log::channel('custom_info')->info($res);
            return $response;
        } catch (\Exception $e) {
            return $next($request);
        }
    }
}
