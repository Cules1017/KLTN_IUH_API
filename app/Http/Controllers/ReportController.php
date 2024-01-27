<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Services\IAdminService;
use App\Services\IReportService;
use App\Services\ISystermConfigService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReportController extends Controller
{
    public $reportService;
    public function __construct(IReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    public function index(Request $request)
    {
        $num = $request->num ? $request->num : 10;
        $page = $request->page ? $request->page : 1;
        $id = $request->id;
        $type_result = $request->type_result;
        $status = $request->status;

        $data = $this->reportService->getList($num, $page, $type_result, $id, $status);
        return $this->sendOkResponse($data);
    }
    public function store(Request $request)
    {
        //case1 update lại khi qua đến client
        global $user_info; //luồng này cho admin update người khác
        if (!isset($user_info->position) || !in_array($user_info->position, [1, 2])) {
            return $this->sendFailedResponse("Không có quyền thao tác", -5, null, 403);
        }
        // Validation rules
        $rules = [
            'key' => ['required','string',Rule::unique('systerm_config')], 
            'value'=>['required','string'],
            'desc'=>['string'],
        ];

        // Custom error messages
        $messages = [
           
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated();
        $data = $this->reportService->create($validator);

        return $this->sendOkResponse($data);
    }

    public function adminUpdate($id, Request $request)
    {
        //case1 update lại khi qua đến client
        // global $user_info; //luồng này cho admin update người khác
        // if (!isset($user_info->position) || !in_array($user_info->position, [1, 2])) {
        //     return $this->sendFailedResponse("Không có quyền thao tác", -5, null, 403);
        // }
        // Validation rules
        $rules = [
            'results'=>['required','string'],
            'status' => ['required',Rule::in([0, 1])],
        ];

        // Custom error messages
        $messages = [
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated();
        $data = $this->reportService->updateAtribute($id, $validator);

        return $this->sendOkResponse($data);
    }
    
   
}
