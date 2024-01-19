<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

trait ApiResponseTrait
{
    protected $response = [
        'status'  => JsonResponse::HTTP_OK,
        'data'    => [],
        'message' => '',
        'result'  => 0,
        'error'   => null
    ];

    /**
     * @param array $responseData
     * @param int $httpCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function apiJsonResponse($responseData = [], int $httpCode = JsonResponse::HTTP_OK, array $headers = [])
    {
        if ($responseData['data'] instanceof JsonResource) {
            $resource = $responseData['data'];
            unset($responseData['data']);
            return $resource->additional(array_merge($this->response, $responseData))
                ->response()
                ->setStatusCode($httpCode)
                ->withHeaders($headers);
        }
        $responseData = array_merge($this->response, $responseData);
        return response()->json($responseData, $httpCode, $headers);
    }

    /**
     * @param array $responseData
     * @param int $httpCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function apiJsonResponseCustom($responseData = [], int $httpCode = JsonResponse::HTTP_OK, array $headers = [])
    {
        $resource = $responseData['data'] ?? [];
        unset($responseData['data']);
        $responseData = array_merge(array_merge($this->response, $resource), $responseData);
        return response()->json($responseData, $httpCode, $headers);
    }

    /**
     * @param string|null $message
     * @param array $data
     * @param int|0 $result
     * @param int $httpCode
     * @return JsonResponse
     */
    protected function sendOkResponse($data = [], ?string $message = 'success', ?int $result = 0, int $httpCode = JsonResponse::HTTP_OK)
    {
		$time_exc=0;
        $timeTraker = [];
        if (defined('LARAVEL_START')) 
            $time_exc = round((microtime((true)) - LARAVEL_START) * 1000);
        

        $aRes=['data' => $data, 'message' => $message, 'result' => $result, 'status' => $httpCode, 'error' => null];
        
        if($time_exc!=0)$aRes=array_merge($aRes,['time_exec'=>$time_exc]);
        if(count($timeTraker)>0)$aRes=array_merge($aRes,['time_log'=>$timeTraker]);
        
        
        return $this->apiJsonResponse($aRes, $httpCode);
    }

    /**
     * @param string|null $message
     * @param array $data
     * @param int|0 $result
     * @param int $httpCode
     * @return JsonResponse
     */
    protected function sendWarningResponse(?string $message = 'warning', ?int $result = 1, $data = [], int $httpCode = JsonResponse::HTTP_OK, $error = null)
    {
        return $this->apiJsonResponse(['data' => $data, 'message' => $message, 'result' => $result, 'status' => $httpCode, 'error' => $error], $httpCode);
    }

    /**
     * @param string|null $message
     * @param array $data
     * @param int|0 $result
     * @param int $httpCode
     * @return JsonResponse
     */
    protected function sendOkResponseCustom($data = [], ?string $message = 'success', ?int $result = 0, int $httpCode = JsonResponse::HTTP_OK)
    {
        return $this->apiJsonResponseCustom(['data' => $data, 'message' => $message, 'result' => $result, 'status' => $httpCode], $httpCode);
    }

    /**
     * @param string|null $message
     * @param array $data
     * @param int|-1 $result
     * @param int $httpCode
     * @return JsonResponse
     */
    protected function sendFailedResponse(?string $message = 'error', ?int $result = -1,  $data = [], int $httpCode = JsonResponse::HTTP_OK, $error = null)
    {
        return $this->apiJsonResponse(['status' => false, 'data' => $data, 'message' => $message, 'result' => $result, 'status' => $httpCode, 'error' => $error],
            $httpCode);
    }

    /**
     * @param string|null $message
     * @param array $data
     * @param int|-100 $result
     * @return JsonResponse
     */
    protected function sendNotFoundResponse(?string $message = 'data not found', ?int $result = -100, $data = [])
    {
        return $this->sendFailedResponse($message, $result, $data, JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * @param string|null $message
     * @param array $data
     * @param int|500 $result
     * @return JsonResponse
     */
    protected function sendBadRequestResponse(?string $message = 'bad request', ?int $result = JsonResponse::HTTP_BAD_REQUEST, $data = [])
    {
        return $this->sendFailedResponse($message, $result, $data, JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * @param $response
     * @return Object
     */
    protected function formatResponsePagination($response)
    {
        return json_decode($response->response()->getContent()) ?? [];
    }
}
