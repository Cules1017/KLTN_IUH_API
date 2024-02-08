<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Job;
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

    public function createNewPost(Request $request)
    {
        $page = $request->page;
        $num = $request->num;
        $data = null;
        global $user_info;
        $client_id = $user_info->id;

        $rules = [
            'title' => 'required|string|max:255',
            'desc' => 'required|string|max:255',
            'content' => 'required|string|max:255',
            'thumbnail' => 'string|max:255',
            'bids' => 'required|numeric|min:0', // Đảm bảo bids là số dương hoặc bằng 0
            'deadline' => 'required|date', // Đảm bảo deadline là kiểu ngày
        ];
        $messages = [
            'required' => 'Trường :attribute là bắt buộc.',
            'exists' => 'Trường :attribute không tồn tại trong bảng :table.',
            'string' => 'Trường :attribute phải là chuỗi.',
            'max' => 'Trường :attribute không được vượt quá :max ký tự.',
            'numeric' => 'Trường :attribute phải là số.',
            'integer' => 'Trường :attribute phải là số nguyên.',
            'min' => 'Trường :attribute phải lớn hơn hoặc bằng :min.',
            'date' => 'Trường :attribute phải là ngày hợp lệ.',
        ];
        // Tạo Validator
        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        // tính min_proposal
        $min_proposal=10;
        $skill=$request->skill;
        $validator = $validator->validated();
        $imagePath = $request->thumbnail ? $request->thumbnail : '';
        if ($request->hasFile('thumbnail')) {
            $imagePath = FileHelper::saveImage($request->file('thumbnail'), 'client', 'avatar');
        }
        $data = $this->jobService->create(array_merge($validator, ['client_id'=>$client_id,'thumbnail' => $imagePath,'skill'=>$skill,'min_proposals'=>$min_proposal, 'status' => 1]));
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

    public function getDetails($id,Request $request){
        return $this->jobService->getById($id);
    }

    public function destroy($id)
    {
        $this->jobService->destroy($id);
        return $this->sendOkResponse();
    }

    public function updateForClient($id,Request $request)
    {
        $page = $request->page;
        $num = $request->num;
        $data = null;
        global $user_info;
        $client_id = $user_info->id;
        $jobInfo=Job::find($id);
        if($jobInfo&&$jobInfo->client_id!=$client_id){
            return $this->sendFailedResponse("Không có quyền chỉnh sửa", -1, "Chỉ có chủ bài mới chỉnh đc, bạn không có quyền chỉnh.", 400);
        }
        elseif($jobInfo==null)
            return $this->sendFailedResponse("Không tìm thấy bài viết", -1, "Không tìm thấy bài viết", 400);
       

        $rules = [
            'title' => 'string|max:255',
            'desc' => 'string|max:255',
            'content' => 'string|max:255',
            'thumbnail' => 'string|max:255',
            'bids' => 'numeric|min:0', // Đảm bảo bids là số dương hoặc bằng 0
            'deadline' => 'date', // Đảm bảo deadline là kiểu ngày
            'status'=> 'numeric', //
        ];
        $messages = [
            'required' => 'Trường :attribute là bắt buộc.',
            'exists' => 'Trường :attribute không tồn tại trong bảng :table.',
            'string' => 'Trường :attribute phải là chuỗi.',
            'max' => 'Trường :attribute không được vượt quá :max ký tự.',
            'numeric' => 'Trường :attribute phải là số.',
            'integer' => 'Trường :attribute phải là số nguyên.',
            'min' => 'Trường :attribute phải lớn hơn hoặc bằng :min.',
            'date' => 'Trường :attribute phải là ngày hợp lệ.',
        ];
        // Tạo Validator
        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        // tính min_proposal
        $min_proposal=10;
        $skill=$request->skill;
        $validator = $validator->validated();
        $imagePath = $request->thumbnail ? $request->thumbnail : $jobInfo->thumbnail;
        if ($request->hasFile('thumbnail')) {
            $imagePath = FileHelper::saveImage($request->file('thumbnail'), 'client', 'avatar');
        }
        $data = $this->jobService->updateWithData($id,array_merge($validator, ['thumbnail' => $imagePath,'skill'=>$skill,'min_proposals'=>$min_proposal]));
        return $this->sendOkResponse($data);
    }
}
