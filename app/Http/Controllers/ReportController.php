<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Client;
use App\Models\Freelancer;
use App\Models\Job;
use App\Models\Report;
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

    public function createReport(Request $request){
        $rules = [
            'type_id' => ['required'], //1 client->freelancer,2 freelancer->client,3// report post,4 other report
            'client_id'=>['nullable'],
            'freelancer_id'=>['nullable'],
            'post_id'=>['nullable'],
            'content'=>['required'],
        ];
        // Custom error messages
        $messages = [
           
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated();
        $filePath = '';
        if ($request->hasFile('content_file')) {
            $filePath = FileHelper::saveImage($request->file('content_file'), 'report', 'avatar');
        }
        $data=Report::create(array_merge($validator,['content_file' => $filePath,'status'=>0,'results'=>'']));
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
        ];

        // Custom error messages
        $messages = [
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $infoReport=Report::find($id);
        $resolve=$request->resolve;
        if($resolve){
            if($infoReport->type_id==1){
                Freelancer::find($infoReport->freelancer_id)->update(['status'=>0]);
            }
            if($infoReport->type_id== 2){
                Client::find($infoReport->client_id)->update(['status'=>0]);
            }
            if($infoReport->type_id==3){
                Job::find($infoReport->post_id)->update(['status'=>0]);
            }
        }
        $validator = $validator->validated();
        $data = $this->reportService->updateAtribute($id, ["status"=>1,'results'=>$request->results]);

        return $this->sendOkResponse($data);
    }
    
   
}
