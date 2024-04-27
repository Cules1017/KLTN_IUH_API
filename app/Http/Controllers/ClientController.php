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
use App\Models\CandidateApplyJob;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use App\Services\IFreelancerService;

class ClientController extends Controller
{
    public $clientService;
    public $freelancerService;
    public function __construct(IClientService $clientService,IFreelancerService $freelancerService)
    {
        $this->clientService = $clientService;
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
        if($imagePath!='')
            $validator = Validator::make(array_merge($rq, ['avatar_url' => $imagePath]), $rules, $messages);
        else
           $validator = Validator::make(array_merge($rq), $rules, $messages); 
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
            $data = $this->freelancerService->autoGetFreelancer($page,$num);
        }
        if ( !$request->keyword == null || !$request->skills == null || !$request->majors == null || !$request->date_of_birth == null||!$request->sex == null) {
            // thực hiện lấy list theo search
            //keyword là search dựa trên các trường intro, address
            //sskill là list skills ex:skills=1,2,3,45,21. search theo id các freelancer có cái skill này bảng skill_freelancer_map
            //expected_salary search dưa trên mức lương mong đợi input là khoảng giá trị cách nhau dấu , expected_salary=1,100
            // sex giá trị 1 là nam 2 là nữ
            $data = $this->freelancerService->searchListFreelancer($page,$num,$request->keyword, $request->skills,$request->majors, $request->date_of_birth, $request->sex);
        } else {
            $data = $this->freelancerService->searchListFreelancer($page,$num,$request->keyword, $request->skills,$request->majors, $request->date_of_birth, $request->sex);
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
            'title'=>'required|string',
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
        // Tạo đối tượng candidate_apply_job
        $candidateApplyJob = CandidateApplyJob::create([
            'freelancer_id' => $validator['freelancer_id'],
            'job_id' => $validator['job_id'],
            'attachment_url' => '',
            'cover_letter'=>'',
            'status'=>2
            //'contract_id'=>$validator['contract_id'],
        ]);
       // unset($insertData['mail_invite']);
        $data=Invite::create($insertData);
        return $this->sendOkResponse($data);


    }

    public function getListInvite(Request $request){
         global $user_info;
        // $InviteInfo=Invite::where('freelancer_id',$user_info->id)
        // ->get()->toArray();
        $num = $request->num ? $request->num : 10;
        $page = $request->page ? $request->page : 1;

        // foreach($InviteInfo as &$invite){
        //     //dd($invite);
        //     $invite['job_info']=Job::find($invite['job_id']);
        //     $invite['client_info']=Client::find($invite['client_id']);
        // }
        // return $this->sendOkResponse($InviteInfo);
        // Lấy thông tin lời mời dựa trên ID của freelancer
    $InviteInfo = Invite::where('freelancer_id', $user_info->id)->paginate($request->num ? $request->num : 10);

    // Thêm thông tin công việc và thông tin khách hàng vào mỗi lời mời
    $InviteInfo->getCollection()->transform(function ($invite) {
    $invite['job_info'] = Job::find($invite['job_id']);
    $invite['client_info'] = Client::find($invite['client_id']);
    return $invite;
});

return $this->sendOkResponse($InviteInfo);

    }

    public function acceptJob($id,Request $request){
        global $user_info;
        $freelancer = Freelancer::find($user_info->id);
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        // Validation rules
        $rules = [
            'status' => ['required', 'integer'],
        ];

        // Custom error messages
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
        $validator = Validator::make($rq, $rules, $messages);

        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }

        $validator = $validator->validated();
        $InviteInfo=Invite::find($id);
        if($InviteInfo==null) return $this->sendFailedResponse("Không tìm thấy lời mời.", -1, "Không tìm thấy lời mời.", 422);
        $jobInfo = Job::find($InviteInfo->job_id);
        if ($jobInfo->status != 1)
        {
            $InviteInfo->status=-1;
            $InviteInfo->save();
            return $this->sendFailedResponse("Công việc đã được client đóng.", -1, "Công việc đã được client đóng.", 422);
        }
            
        $countDB=DB::select('SELECT count(*) as c FROM candidate_apply_job WHERE job_id='.$InviteInfo->job_id.' AND status>2');
        if ($countDB[0]->c>0)
        {
            $InviteInfo->status=-1;
            $InviteInfo->save();
            return $this->sendFailedResponse("Công việc đã có người thực hiện.", -1, "Công việc đã có người thực hiện.", 422);
        }
            
        if($request->status==-1){
            $InviteInfo->status=-1;
            $InviteInfo->save();
            return $this->sendOkResponse('Bạn đã từ chối lời mời công việc');
        }
        
        $InviteInfo->status=1;
        $InviteInfo->save();

        // Trả về kết quả
        return $this->sendOkResponse("Chấp nhận job thành công");
    }


    public function getMajors(Request $request){
        $page=$request->page?$request->page:1;
        $num =$request->num?$request->num:10;
        $query = DB::table('majors');
        $title_major=$request->title_major?$request->title_major:$request->titleMajor;
        // Tìm kiếm theo từ khóa
        if (!empty($title_major)) {
            $query->where('title_major', 'LIKE', "%".$title_major."%");
        }
        $data=$query->select('majors.*')->distinct()
                ->paginate($num, ['*'], 'page', $page);
        return [
                'data' => $data->items(),
                'total' => $data->total(),
                'total_page' => $data->lastPage(),
                'num' => $num,
                'current_page' => $page,
            ];
    }
}
