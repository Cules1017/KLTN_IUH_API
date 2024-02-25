<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\MyHelper;
use App\Models\Client;
use App\Models\Freelancer;
use App\Models\Invite;
use App\Services\IAdminService;
use App\Services\IClientService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientController extends Controller
{
    public $clientService;
    public function __construct(IClientService $clientService)
    {
        $this->clientService = $clientService;
    }
    public function index(Request $request)
    {
        $num = $request->num ? $request->num : 10;
        $page = $request->page ? $request->page : 1;
        $searchValue = $request->search ? $request->search : '';
        $id = $request->id;
        $sex = $request->sex;
        $status = $request->status;

        $data = $this->clientService->getList($num, $page, $searchValue, $id, $status, $sex);
        return $this->sendOkResponse($data);
    }

    public function update($id, Request $request)
    {
        //case1 update lại khi qua đến client
        global $user_info;

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
        $data = $this->clientService->updateAtribute($id, $validator);

        return $this->sendOkResponse($data);
    }
    public function updateForClient(Request $request)
    {
        global $user_info;
        $id = $user_info->id;
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        // Validation rules
        $rules = [
            'username' => ['max:255', Rule::unique('client')->ignore($id), Rule::unique('freelancer'), Rule::unique('admin')],
            'email' => ['email', 'max:255', Rule::unique('client')->ignore($id), Rule::unique('freelancer'), Rule::unique('admin')],
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'phone_num' => 'nullable|string',
            'address' => 'nullable|string',
            'sex' => 'nullable|integer',
            'date_of_birth' => 'nullable|date',
            'avatar_url' => 'nullable|string',
            'company_name' => 'nullable|string',
            'introduce' => 'nullable|string',
            'bank_account' => 'nullable|exists:bank_accounts,id',
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
        $imagePath = '';
        if ($request->hasFile('avatar')) {
            $imagePath = FileHelper::saveImage($request->file('avatar'), 'client', 'avatar');
        }

        $validator = Validator::make(array_merge($rq, ['avatar_url' => $imagePath]), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated();
        $data = $this->clientService->updateAtribute($id, $validator);
        return $this->sendOkResponse($data);
    }

    public function getInfoClient(Request $request)
    {
        global $user_info;
        $id = $user_info->id;
        $data = $this->clientService->getById($id);
        return $this->sendOkResponse($data);
    }
    public function destroy($id)
    {
        global $user_info;
        if (!in_array($user_info->position, [1])) {
            return $this->sendFailedResponse("Không có quyền thao tác", -5, null, 403);
        }
        $this->clientService->destroy($id);
        return $this->sendOkResponse();
    }


    public function getListFreelancer(Request $request)
    {
        $data=[];
        $num = $request->num ? $request->num : 10;
        $page = $request->page ? $request->page : 1;
        if($request->recommend==1){
            // lấy thông tin job của user hiện tại và lấy các kỹ năng liên quan và đưa ra danh sách cá freelancer có kỹ năng liên quan
            $data = $this->clientService->autoGetFreelancer($page,$num);
        }
        if ( !$request->keyword == null || !$request->skills == null || !$request->date_of_birth == null||!$request->expected_salary == null||!$request->sex == null) {
            // thực hiện lấy list theo search
            //keyword là search dựa trên các trường intro, address
            //sskill là list skills ex:skills=1,2,3,45,21. search theo id các freelancer có cái skill này bảng skill_freelancer_map
            //expected_salary search dưa trên mức lương mong đợi input là khoảng giá trị cách nhau dấu , expected_salary=1,100
            // sex giá trị 1 là nam 2 là nữ
            $data = $this->clientService->searchListFreelancer($page,$num,$request->keyword, $request->skills, $request->date_of_birth, $request->expected_salary, $request->sex);
        } else {
            $data = $this->clientService->searchListFreelancer($page,$num,$request->keyword, $request->skills, $request->date_of_birth, $request->expected_salary, $request->sex);
        }
        return $this->sendOkResponse($data);
    }


    public function inviteJob(Request $request){
        global $user_info;
        $id_client = $user_info->id;
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        $rules = [
            'job_id' => 'required|exists:jobs,id',
            'freelancer_id' => 'required|exists:freelancer,id',
            'mail_invite'=>'required|string',
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
        $validator = Validator::make($rq, $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated();
        $vali=Invite::where('job_id',"=",$validator['job_id'])->where('freelancer_id',"=",$validator['freelancer_id'])->get()->toArray();
        if(count($vali)>0) return $this->sendFailedResponse("người này đã được mời vào job này", -1, "người này đã được mời", 422);
        $vali=Invite::where('job_id',"=",$validator['job_id'])->where('status','=',1)->get()->toArray();
        if(count($vali)>0) return $this->sendFailedResponse("job này đã có người nhận việc", -1, "job này đã có người nhận việc", 422);
        $insertData=array_merge($validator,["client_id"=>$id_client, "status"=>0,]);
        $infoFreelancer=Freelancer::find($validator['freelancer_id']);
        
        Mail::send('mailinvite', ['company_name' => $user_info->company_name,'message_mail'=>$validator['mail_invite']], function ($message) use ($infoFreelancer,$user_info) {
            $message->to($infoFreelancer->email, $infoFreelancer->first_name)->subject("Thư mời làm việc từ ".$user_info->company_name);
        });
        unset($insertData['mail_invite']);
        $data=Invite::create($insertData);
        return $this->sendOkResponse($data);


    }
}
