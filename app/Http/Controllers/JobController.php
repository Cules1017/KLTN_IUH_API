<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Services\IAdminService;
use App\Services\IJobService;
use App\Services\ISystermConfigService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class JobController extends Controller
{
    public $jobService;
    public function __construct(IJobService $jobService)
    {
        $this->jobService = $jobService;
    }
    public function index(Request $request)
    {
        $num = $request->num ? $request->num : 10;
        $page = $request->page ? $request->page : 1;
        $searchValue = $request->search ? $request->search : '';
        $client_info = $request->client_info;
        $min_proposal = $request->min_proposal;
        $id = $request->id;
        $bids = $request->bids;
        $status = $request->status;

        $data = $this->jobService->getList($num, $page, $searchValue, $client_info, $min_proposal, $id, $status, $bids);
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
            'key' => ['required', 'string', Rule::unique('systerm_config')],
            'value' => ['required', 'string'],
            'desc' => ['string'],
        ];

        // Custom error messages
        $messages = [];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated();
        $data = $this->jobService->create($validator);

        return $this->sendOkResponse($data);
    }

    public function updateAdmin($id, Request $request)
    {
        // Validation rules
        $rules = [
            'status' => ['required'],
        ];

        // Custom error messages
        $messages = [];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated();
        $data = $this->jobService->updateAtribute($id, $validator);

        return $this->sendOkResponse($data);
    }

    public function getMyPost(Request $request)
    {
        $page = $request->page;
        $num = $request->num;
        $data = null;
        global $user_info;
        $client_id = $user_info->id;
        $atributes = ['client_id'];
        $value = [$client_id];
        // Validation rules
        if ($request->status !== null) {
            array_push($atributes, 'status');
            array_push($value, $request->status);
        }
        if ($page && $num) {
            $data = $this->jobService->getJobByAtribute($atributes, $value, $page, $num);
        } else {
            $data = $this->jobService->getJobByAtribute($atributes, $value);
        }
        return $this->sendOkResponse($data);
    }
    // public function createNewPost(Request $request)
    // {
    //     $page = $request->page;
    //     $num = $request->num;
    //     $data = null;
    //     global $user_info;
    //     $client_id = $user_info->id;
    //     $atributes = ['client_id'];
    //     $value = [$client_id];
    //     // Validation rules
    //     if ($request->status !== null) {
    //         array_push($atributes, 'status');
    //         array_push($value, $request->status);
    //     }
    //     if ($page && $num) {
    //         $data = $this->jobService->getJobByAtribute($atributes, $value, $page, $num);
    //     } else {
    //         $data = $this->jobService->getJobByAtribute($atributes, $value);
    //     }
    //     return $this->sendOkResponse($data);
    // }

    public function destroy($id)
    {
        $this->jobService->destroy($id);
        return $this->sendOkResponse();
    }
}
