<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\MyHelper;
use App\Models\CandidateApplyJob;
use App\Models\Freelancer;
use App\Models\Job;
use App\Models\Tasks;
use App\Services\IAdminService;
use App\Services\IJobService;
use App\Services\ISystermConfigService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $min_proposal = 10;
        $skill = $request->skill;
        $validator = $validator->validated();
        $imagePath = $request->thumbnail ? $request->thumbnail : '';
        if ($request->hasFile('thumbnail')) {
            $imagePath = FileHelper::saveImage($request->file('thumbnail'), 'client', 'avatar');
        }
        $data = $this->jobService->create(array_merge($validator, ['client_id' => $client_id, 'thumbnail' => $imagePath, 'skill' => $skill, 'min_proposals' => $min_proposal, 'status' => 1]));
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

    public function getDetails($id, Request $request)
    {
        $status_arr= [0=>"ẩn",1=> "mở apply",2=>"đóng apply",3=>"đang được thực hiện" ];
        $data=$this->jobService->getById($id);
        $data['status_text']=$status_arr[$data->status];
        $data['tasks']=Tasks::where('job_id',"=",$id)->get();
        $data['applied']=CandidateApplyJob::where('job_id', $id) ->orderBy('candidate_apply_job.proposal', 'desc')
        ->join('freelancer', 'freelancer.id', '=', 'candidate_apply_job.freelancer_id')
        ->select('candidate_apply_job.*', 'freelancer.username', 'freelancer.email',)
        ->get();
        $data['applied_count']=count($data['applied']);
        $data['nominee']=CandidateApplyJob::where('job_id', $id) ->where('candidate_apply_job.status',">=", 2) ->orderBy('candidate_apply_job.proposal', 'desc')
        ->join('freelancer', 'freelancer.id', '=', 'candidate_apply_job.freelancer_id')
        ->select('candidate_apply_job.*', 'freelancer.username', 'freelancer.email',)
        ->first();
        return $this->sendOkResponse($data);
    }

    public function destroy($id)
    {
        $this->jobService->destroy($id);
        return $this->sendOkResponse();
    }

    public function updateForClient($id, Request $request)
    {
        $page = $request->page;
        $num = $request->num;
        $data = null;
        global $user_info;
        $client_id = $user_info->id;
        $jobInfo = Job::find($id);
        if ($jobInfo && $jobInfo->client_id != $client_id) {
            return $this->sendFailedResponse("Không có quyền chỉnh sửa", -1, "Chỉ có chủ bài mới chỉnh đc, bạn không có quyền chỉnh.", 400);
        } elseif ($jobInfo == null)
            return $this->sendFailedResponse("Không tìm thấy bài viết", -1, "Không tìm thấy bài viết", 400);


        $rules = [
            'title' => 'string|max:255',
            'desc' => 'string|max:255',
            'content' => 'string|max:255',
            'thumbnail' => 'string|max:255',
            'bids' => 'numeric|min:0', // Đảm bảo bids là số dương hoặc bằng 0
            'deadline' => 'date', // Đảm bảo deadline là kiểu ngày
            'status' => 'numeric', //
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
        $min_proposal = 10;
        $skill = $request->skill;
        $validator = $validator->validated();
        $imagePath = $request->thumbnail ? $request->thumbnail : $jobInfo->thumbnail;
        if ($request->hasFile('thumbnail')) {
            $imagePath = FileHelper::saveImage($request->file('thumbnail'), 'client', 'avatar');
        }
        $data = $this->jobService->updateWithData($id, array_merge($validator, ['thumbnail' => $imagePath, 'skill' => $skill, 'min_proposals' => $min_proposal]));
        return $this->sendOkResponse($data);
    }

    public function getJobListForFreelancer(Request $request)
    {
        // các trường cho phép search
        //keyword trường này cho phép search thông tin jobs
        //bids giá trị trong khoảng [a,b] truyền lên bids=143,224
        //status trạng thái job cứ truyền theo trạng thái
        //proposal trường này search theo min proposal truyền lên proposal=0,1.5
        //deadline trường này truyền lên khoảng thời gian deadline=yyyy-MM-dd,yyyy-MM-dd
        if ($request->skills || $request->keyword || $request->bids || $request->status || $request->proposal || $request->deadline) {
            $data = $this->jobService->getListJobFillterForFreelancer($request->page, $request->num, $request->skills, $request->keyword, $request->bids, $request->status, $request->proposal, $request->deadline);
        } else {
            $data = $this->jobService->getListJobForFreelancer($request->page, $request->num);
        }

        return $this->sendOkResponse($data);
    }

    public function FreelancerApplyJob(Request $request)
    {
        global $user_info;
        $freelancer = Freelancer::find($user_info->id);
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        // Validation rules
        $rules = [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'proposal' => ['required', 'numeric'],
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
        $jobInfo = Job::find($validator['job_id']);
        if ($jobInfo->status != 1)
            return $this->sendFailedResponse("Công việc đang ở trạng thái không thể apply.", -1, "Công việc đang ở trạng thái không thể apply.", 422);
        if ($validator['proposal'] < $jobInfo->min_proposals)
            return $this->sendFailedResponse("Vui lòng nhập proposal lớn hơn giá trị min.", -1, "Vui lòng nhập proposal lớn hơn giá trị min.", 422);
        if ($validator['proposal'] > $freelancer->available_proposal)
            return $this->sendFailedResponse("Không đủ proposal để apply.", -1, "Không đủ proposal để apply.", 422);
        $cvUrl = $request->cvUrl ? $request->cvUrl : '';
        if ($request->hasFile('cvUrl')) {
            $cvUrl = FileHelper::saveImage($request->file('cvUrl'), 'cv_freelancer', 'CV');
        }
        // Tạo đối tượng candidate_apply_job
        $candidateApplyJob = CandidateApplyJob::create([
            'freelancer_id' => $freelancer->id,
            'job_id' => $validator['job_id'],
            'proposal' => $validator['proposal'],
            'cv_url' => $cvUrl,
        ]);
        $freelancer->available_proposal = $freelancer->available_proposal - $validator['proposal'];
        $freelancer->save();
        $jobInfo->status = 2;
        $jobInfo->save();


        // Trả về kết quả
        return $this->sendOkResponse(["available_proposal" => $freelancer->available_proposal, "job" => $jobInfo, "apply" => $candidateApplyJob], "Apply job thành công.");
    }
    public function getFreelancerAppliedJob(Request $request)
    {
        global $user_info;
        $appliedJobs = DB::table('candidate_apply_job')
            ->join('jobs', 'candidate_apply_job.job_id', '=', 'jobs.id')
            ->where('candidate_apply_job.freelancer_id', $user_info->id)
            ->select('candidate_apply_job.status as job_ap_status', 'candidate_apply_job.cv_url', 'jobs.*')
            ->get();
        foreach ($appliedJobs as &$job) {
            $status='';
            switch ($job->job_ap_status) {
                case 1:
                    $status = 'Đã apply';
                    break;
                case -1:
                    $status = 'Đã bị loại';
                    break;
                case 2:
                    $status = 'Được mời';
                    break;
                case 3:
                    $status = 'Công việc đang trong thời gian';
                    break;
                case 4:
                    $status = 'Công việc hoàn tất';
                    break;
                default:
                    $status = 'Không xác định';
            }
            $job->status_apply_text= $status;
        }
        return $this->sendOkResponse($appliedJobs);
    }
    public function getTaskByJob($id,Request $request){
        $data=Tasks::where('job_id',"=",$id);
        return $this->sendOkResponse($data);
    }
    public function addTask($id, Request $request){
        $rq = MyHelper::convertKeysToSnakeCase(array_merge($request->all(),['job_id'=>$id]));
        // Validation rules
        $rules = [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'name' => ['required', 'string'],
            'desc'=> ['required', 'string'],
            'deadline' => ['required', 'string']
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
        $data=Tasks::create(array_merge($validator,['status'=>-1,'confirm_status'=>-1]));
        return $this->sendOkResponse($data);
    }

    public function freelancerSetStatus($id,Request $request){
        $task = Tasks::find($id);
        $task->status = $request->status;
        $task->save();
        return $this->sendOkResponse($task);
    }
    public function clientConfirmStatus($id,Request $request){
        $task = Tasks::find($id);
        $task->confirm_status = $request->confirm_status;
        if($request->confirm_status==0)
            $task->status =0;
        $task->save();
        return $this->sendOkResponse($task);
    }

    public function destroyTask($id,Request $request){
        $task = Tasks::find($id);
        $task->destroy();
        return $this->sendOkResponse();
    }
    public function recruitmentConfirmation ($id,Request $request){
        $infoApply=CandidateApplyJob::find($id);
        if($infoApply==null)
            return $this->sendFailedResponse("Không tìm thấy thông tin ứng tuyển với ID đã cung cấp", -1, "Không tìm thấy thông tin ứng tuyển với ID đã cung cấp", 422);
        $ListApply=CandidateApplyJob::where('job_id',$infoApply->job_id)->where('status','>=',2)->get();
        if(count($ListApply)>1){
            foreach($ListApply as $apply){
                $apply->status=1;
                $apply->save();
            }
            return $this->sendFailedResponse("thông tin lỗi vui lòng thử lại.", -1, "thông tin lỗi vui lòng thử lại.", 422);
        }
        $ListApply=CandidateApplyJob::where('job_id',$infoApply->job_id)->get();
        //dd($ListApply);
        foreach($ListApply as $apply){
            if($apply->id==$id){
                
                $apply->status=2;
                $apply->save();
            }
            else{
                $apply->status=-1;
                $apply->save();
            }
        }
        return $this->sendOkResponse("ok");

    }
}
