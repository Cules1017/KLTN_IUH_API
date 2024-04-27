<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\MyHelper;
use App\Services\IAdminService;
use App\Services\IFreelancerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\MyList;

class FreelancerController extends Controller
{
    public $freelancerService;
    public function __construct(IFreelancerService $freelancerService)
    {
        $this->freelancerService = $freelancerService;
    }
    public function index(Request $request)
    {
        $num = $request->num ? $request->num : 10;
        $page = $request->page ? $request->page : 1;
        $searchValue = $request->search ? $request->search : '';
        $id = $request->id;
        $sex = $request->sex;
        $status = $request->status;

        $data = $this->freelancerService->getList($num, $page, $searchValue, $id, $status, $sex);
        return $this->sendOkResponse($data);
    }

    public function update($id, Request $request)
    {
        //case1 update lại khi qua đến client
        global $user_info; //luồng này cho admin update người khác
        if (!isset($user_info->position) || !in_array($user_info->position, [1, 2])) {
            return $this->sendFailedResponse("Không có quyền thao tác", -5, null, 403);
        }
        // Validation rules
        $rules = [
            'status' => [Rule::in([0, 1])], // 0-> trạng thái khóa, 1- trạng thái hoạt động
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
        $data = $this->freelancerService->updateAtribute($id, $validator);

        return $this->sendOkResponse($data);
    }
    public function destroy($id)
    {
        global $user_info;
        if (!in_array($user_info->position, [1])) {
            return $this->sendFailedResponse("Không có quyền thao tác", -5, null, 403);
        }
        $this->freelancerService->destroy($id);
        return $this->sendOkResponse();
    }
    public function getInfoUser(Request $request){
        global $user_info;
        $id = $user_info->id;
        $data = $this->freelancerService->getById($id);
        return $this->sendOkResponse($data);
    }
    public function updateForFreelancer(Request $request)
    {
        global $user_info;
        $id = $user_info->id;
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        // Validation rules
        $rules = [
            'username' => ['max:255', Rule::unique('client'), Rule::unique('freelancer')->ignore($id), Rule::unique('admin')],
            'email' => ['email', 'max:255', Rule::unique('client'), Rule::unique('freelancer')->ignore($id), Rule::unique('admin')],
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'phone_num' => 'nullable|string',
            'address' => 'nullable|string',
            'sex' => 'nullable|integer',
            'date_of_birth' => 'nullable|date',
            'avatar_url' => 'nullable|string',
            'intro' => 'nullable|string',
        ];

        // Custom error messages
        $messages = [
            'required' => 'Trường :attribute là bắt buộc.',
            'unique' => 'Trường :attribute đã tồn tại.',
            'email' => 'Trường :attribute phải là địa chỉ email hợp lệ.',
            'string' => 'Trường :attribute phải là chuỗi.',
            'integer' => 'Trường :attribute phải là số nguyên.',
            'date' => 'Trường :attribute phải là ngày hợp lệ.',
            'timestamp' => 'Trường :attribute phải là timestamp hợp lệ.',
            'exists' => 'Trường :attribute không tồn tại.',
            'in' => 'Trường :attribute không hợp lệ.',
        ];
        $imagePath = $request->avatar_url;
        if ($request->hasFile('avatar')) {
            $imagePath = FileHelper::saveImage($request->file('avatar'), 'client', 'avatar');
        }
        $skill=$request->skill?explode(',', $request->skill):null;
        $majors=$request->majors;
        $validator=[];
        if($imagePath!=null)
            $validator = Validator::make(array_merge($rq, ['avatar_url' => $imagePath,'skill'=>$skill]), $rules, $messages);
        else
            $validator = Validator::make(array_merge($rq, ['skill'=>$skill]), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated();
        $data=$this->freelancerService->updateAtribute($id,array_merge($validator,['avatar_url' => $imagePath,'skill'=>$skill,'majors'=>$majors]));
        return $this->sendOkResponse($data);    

    }

    public function AddJob($id, Request $request){
        global $user_info;
        $mid = $user_info->id;
        $userType=$user_info->user_type;
        $arrayInsert=['type_user'=>$userType,'user_id'=>$mid];
        if($userType=='freelancer'){
            $arrayInsert=array_merge($arrayInsert,['job_id'=>$id]);
        }else{
            $arrayInsert=array_merge($arrayInsert,['freelancer'=>$id]);
        }
        $iExist=MyList::where($arrayInsert)->get()->toArray();
        if(count($iExist)>0){
            MyList::where($arrayInsert)->delete();
            return $this->sendOkResponse('Đã xóa thành công khỏi list của bạn.'); 
        }
        $data=MyList::create($arrayInsert);
        return $this->sendOkResponse('Đã thêm thành công vào list của bạn.'); 
    }

    public function GetMyJob(){
        global $user_info;
        $mid = $user_info->id;
        $userType=$user_info->user_type;
        $arrayInsert=['type_user'=>$userType,'user_id'=>$mid];
        $iExist=MyList::where($arrayInsert)->get()->toArray();
        return $this->sendOkResponse($iExist);
    }
}
