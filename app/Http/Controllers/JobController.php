<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\MyHelper;
use App\Models\CandidateApplyJob;
use App\Models\Client;
use App\Models\Freelancer;
use App\Models\Job;
use App\Models\Tasks;
use App\Models\Invite;
use App\Models\Comment;
use App\Models\FeedBacks;
use App\Models\Skill;
use App\Services\IAdminService;
use App\Services\IJobService;
use App\Services\INotificationService;
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
    public $notiService;
    public function __construct(IJobService $jobService, INotificationService $notiService)
    {
        $this->jobService = $jobService;
        $this->notiService=$notiService;
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
        $data = null;
        global $user_info;
        $client_id = $user_info->id;

        $rules = [
            'title' => 'required|string',
            'desc' => 'required|string',
            'content' => 'required|string',
            'thumbnail' => 'nullable',
            'bids' => 'required|numeric|min:0', // Đảm bảo bids là số dương hoặc bằng 0
            'deadline' => 'required|date', // Đảm bảo deadline là kiểu ngày
            'status'=>'required|numeric',
            'skill'=>'string', //
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
        $skill = $request->skill?explode(',', $request->skill):null;
        $validator = $validator->validated();
        $imagePath = $request->thumbnail ? $request->thumbnail : '';
        $contentFile=null;
        if ($request->hasFile('thumbnail')) {
            $imagePath = FileHelper::saveImage($request->file('thumbnail'), 'client', 'avatar');
        }
        if ($request->hasFile('content_file')) {
            $contentFile = FileHelper::saveImage($request->file('content_file'), 'client/file_job', 'file_job');
        }
        $data = $this->jobService->create(array_merge($validator, ['client_id' => $client_id,'content_file'=>$contentFile, 'thumbnail' => $imagePath,'skill'=>$skill]));
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
            $data = $this->jobService->getJobByAtribute($atributes, $value, 1, 100000);
        }
        return $this->sendOkResponse($data);
    }

    public function getDetails($id, Request $request)
    {
        $status_arr = [0 => "ẩn", 1 => "mở apply", 2 => "đóng apply", 3 => "đang được thực hiện"];
        $data = $this->jobService->getById($id);
        if($data==[]) return $this->sendFailedResponse("Không tìm thấy thông tin chi tiết", -1, "Không tìm thấy thông tin chi tiết", 400);
        $data['status_text'] = $status_arr[$data->status];
        $data['tasks'] = Tasks::where('job_id', "=", $id)->get();
        $data['applied'] = CandidateApplyJob::where('job_id', $id)->orderBy('candidate_apply_job.created_at', 'desc')
            ->join('freelancer', 'freelancer.id', '=', 'candidate_apply_job.freelancer_id')
            ->where('candidate_apply_job.status','!=','2')
            ->select('candidate_apply_job.*', 'freelancer.username', 'freelancer.email',)
            ->get();
        $data['applied_count'] = count($data['applied']);
        $data['nominee'] = CandidateApplyJob::where('job_id', $id)->where('candidate_apply_job.status', ">", 2)->orderBy('candidate_apply_job.created_at', 'desc')
            ->join('freelancer', 'freelancer.id', '=', 'candidate_apply_job.freelancer_id')
            ->select('candidate_apply_job.*', 'freelancer.username', 'freelancer.email',)
            ->first();
        
        $data['list_invite']=Invite::where('job_id', $id)
            ->join('freelancer', 'freelancer.id', '=', 'invite.freelancer_id')
            ->select('invite.*', 'freelancer.username', 'freelancer.email',)
            ->get();
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
        $skill = $request->skill?explode(',', $request->skill):null;
        $validator = $validator->validated();
        $imagePath = $request->thumbnail ? $request->thumbnail : $jobInfo->thumbnail;
        if ($request->hasFile('thumbnail')) {
            $imagePath = FileHelper::saveImage($request->file('thumbnail'), 'client', 'avatar');
        }
        $data = $this->jobService->updateWithData($id, array_merge($validator, ['thumbnail' => $imagePath, 'skill' => $skill]));
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
        if (isset($request->skills) || isset($request->keyword) || isset($request->bids) || isset($request->status) || isset($request->proposal) || isset($request->deadline)) {
            $data = $this->jobService->getListJobFillterForFreelancer($request->page, $request->num, $request->skills, $request->keyword, $request->bids, $request->status, $request->deadline);
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
            //'contract_id'=>['required', 'numeric'],
            'cover_letter'=>['string'],
            //'contract_id'=>['required', 'numeric'],
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
        $countDB=DB::select('SELECT count(*) as c FROM candidate_apply_job WHERE job_id='.$validator['job_id'].' AND freelancer_id='.$freelancer->id);
        if ($countDB[0]->c>0)
            return $this->sendFailedResponse("Công việc này bạn đã apply.", -1, "Công việc này bạn đã apply.", 422);
        
        //if ($validator['proposal'] < $jobInfo->min_proposals)
        //    return $this->sendFailedResponse("Vui lòng nhập proposal lớn hơn giá trị min.", -1, "Vui lòng nhập proposal lớn hơn giá trị min.", 422);
        // if ($validator['proposal'] > $freelancer->available_proposal)
        //     return $this->sendFailedResponse("Không đủ proposal để apply.", -1, "Không đủ proposal để apply.", 422);
        $cvUrl = $request->cvUrl ? $request->cvUrl : '';
        if ($request->hasFile('attachmentUrl')) {
            $cvUrl = FileHelper::saveImage($request->file('attachmentUrl'), 'cv_freelancer', 'CV');
        }
        // Tạo đối tượng candidate_apply_job
        $candidateApplyJob = CandidateApplyJob::create([
            'freelancer_id' => $freelancer->id,
            'job_id' => $validator['job_id'],
            'attachment_url' => $cvUrl,
            'cover_letter'=>$validator['cover_letter'],
            //'contract_id'=>$validator['contract_id'],
        ]);
        // $freelancer->available_proposal = $freelancer->available_proposal - $validator['proposal'];
        // $freelancer->save();
        // $jobInfo->status = 2;
        // $jobInfo->save();


        // Trả về kết quả
        return $this->sendOkResponse(["job" => $jobInfo, "apply" => $candidateApplyJob], "Apply job thành công.");
    }
    public function getFreelancerAppliedJob(Request $request)
    {
        global $user_info;
        $query=['candidate_apply_job.freelancer_id'=> $user_info->id
        ];
        if($request->status!=null){
            $query=array_merge($query,['candidate_apply_job.status'=>$request->status]);
        }
        $page=$request->page?$request->page:1;
        $num=$request->num?$request->num:10;
        $appliedJobs = DB::table('candidate_apply_job')
            ->join('jobs', 'candidate_apply_job.job_id', '=', 'jobs.id')
            ->where($query)
            ->select('candidate_apply_job.status as job_ap_status', 'candidate_apply_job.*', 'jobs.*')
            ->paginate($num, ['*'], 'page', $page);
            //->get();
        
        foreach ($appliedJobs as &$job) {
            $status = '';
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
            $job->status_apply_text = $status;
        }
       $appliedJobs=$appliedJobs->toArray();
       //dd($appliedJobs);
        $appliedJobs=[
            'data'=>$appliedJobs['data'],
            'current_page'=>$appliedJobs['current_page'],
            'total'=>$appliedJobs['total'],
            'num'=>$num,
            'total_page'=>$appliedJobs['last_page']
        ];
        return $this->sendOkResponse($appliedJobs);
    }
    public function getTaskByJob($id, Request $request)
    {
       // try {
            $JobInfo = Job::findorFail($id);
            $data = Tasks::where('job_id', '=', $id)->get();
            $mappingText1 = ["-1" => "Đã được giao", "0" => "Đang thực hiện", "1" => "Đã hoàn thành", "2" => "Đã được client xác nhận"];
            $mappingText2 = ["0" => "Chưa xác nhận", "1" => "Đã xác nhận"];
            foreach ($data as $key => $value) {
                // Thêm các trường vào mảng $data
                $data[$key]['status_text'] = $mappingText1[$value['status']];
                $data[$key]['status_confirm_text'] = $mappingText2[$value['confirm_status']];
                $data[$key]['comment']=Comment::where('task_id',$value['id'])->orderBy('created_at', 'asc')->get();
            }
            return $this->sendOkResponse($data);
        //} catch (\Throwable $th) {
        //    return $this->sendFailedResponse("Có lỗi khi lấy task vui lòng thử lại! Hãy chắc chắn là job tồn tại");
       /// }
    }
    public function addTask($id, Request $request)
    {
        $rq = MyHelper::convertKeysToSnakeCase(array_merge($request->all(), ['job_id' => $id]));
        // Validation rules
        $rules = [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'name' => ['required', 'string'],
            'desc' => ['required', 'string'],
            'deadline' => ['required', 'string'],
            'priority'=>['required']
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
        try {
            $JobInfo = Job::findorFail($id);
            $data = Tasks::create(array_merge($validator, ['status' => -1, 'confirm_status' => 0]));
            $infoApply = CandidateApplyJob::where('job_id', $id)->where('status',3)->first();
            if($infoApply==null){
                return $this->sendBadRequestResponse("Công việc chưa có người thực hiện");
            }
            $user_info=Freelancer::find($infoApply->freelancer_id);
            $user_info['user_type']='freelancer';
            $this->notiService->pushNotitoUser($user_info,['linkable'=>'hahaha','image'=>'https://d57439wlqx3vo.cloudfront.net/iblock/f5d/f5dcf76697107ea302a1981718e33c95/1f68f84b53199df9cae4b253225eae63.png','title'=>"[$JobInfo->title] Thêm Công Việc Mới",'message'=>"$data->name $data->desc"],true);
            return $this->sendOkResponse($data);
        } catch (\Throwable $th) {
            return $this->sendFailedResponse("Có lỗi khi lấy task vui lòng thử lại! Hãy chắc chắn là job tồn tại");
        }
    }

    public function editTask($id, Request $request)
    {
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        // Validation rules
        $rules = [
            'job_id' => ['integer', 'exists:jobs,id'],
            'name' => ['string'],
            'desc' => ['string'],
            'deadline' => [ 'string'],
            'priority'=>['integer']
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
        try {
           // $data = Tasks::create(array_merge($validator, ['status' => -1, 'confirm_status' => 0]));
            $data= Tasks::whereId($id)->update($validator);
            $data=Tasks::find($id);
            // $infoApply = CandidateApplyJob::where('job_id', $id)->where('status',3)->first();
            // if($infoApply==null){
            //     return $this->sendBadRequestResponse("Công việc chưa có người thực hiện");
            // }
            // $user_info=Freelancer::find($infoApply->freelancer_id);
            // $user_info['user_type']='freelancer';
            $data['comment']=Comment::where('task_id',$id)->orderBy('created_at', 'asc')->get();
            // $this->notiService->pushNotitoUser($user_info,['linkable'=>'hahaha','image'=>'https://d57439wlqx3vo.cloudfront.net/iblock/f5d/f5dcf76697107ea302a1981718e33c95/1f68f84b53199df9cae4b253225eae63.png','title'=>"[$JobInfo->title] Thêm Công Việc Mới",'message'=>"$data->name $data->desc"],true);
            return $this->sendOkResponse($data);
        } catch (\Throwable $th) {
            return $this->sendFailedResponse("Có lỗi khi truy vấn vui lòng thử lại! Hãy chắc chắn là job tồn tại");
        }
    }
    public function addCommentTask($id, Request $request)
    {
       $rq = MyHelper::convertKeysToSnakeCase($request->all());
       $rq['task_id']=$id;
        // Validation rules
        $rules = [
            'content'=>['required'],
            'task_id'=>['required'],
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
        $validator['type']='text';
        if ($request->hasFile('content')) {
            $validator['content'] = FileHelper::saveImage($request->file('content'), 'comment', 'avatar');
            $validator['type']='file';
        }
        global $user_info;
        $validator['user_id']=$user_info->id;
        $validator['user_type']=$user_info->user_type;
        
         try {
            global $user_info;
            $task = Tasks::findorFail($id);
            //dd($validator);
            $comment=Comment::create($validator);
            return $this->sendOkResponse($comment);
          } catch (\Throwable $th) {
            return $this->sendFailedResponse("Có lỗi khi truy vấn vui lòng thử lại! Hãy chắc chắn là task tồn tại");
        }
    }
    public function deleteCommentTask($id, Request $request)
    {
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        $rq['id']=$id;
        // Validation rules
        $rules = [
            'id'=>['required','integer']
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
        //try {
            global $user_info;
            $comment = Comment::findorFail($id);
            $comment->delete();
          return $this->sendOkResponse('thành công','thành công');
        //} catch (\Throwable $th) {
        //return $this->sendFailedResponse("Có lỗi khi truy vấn vui lòng thử lại! Hãy chắc chắn là comment tồn tại");
        //}
    }

    public function freelancerSetStatus($id, Request $request)
    {
        global $user_info;
        $task = Tasks::find($id);
        $task->status = $request->status;
        $task->save();
        $Job=Job::find($task->job_id);
        $user_info1=Client::find($Job->client_id);
        $user_info1['user_type']='client';
        $this->notiService->pushNotitoUser($user_info1,['linkable'=>'hahaha','image'=>'https://d57439wlqx3vo.cloudfront.net/iblock/f5d/f5dcf76697107ea302a1981718e33c95/1f68f84b53199df9cae4b253225eae63.png','title'=>"$user_info->first_name Đã set Status công việc",'message'=>"aaaaa"],true);
        $task['comment']=Comment::where('task_id',$id)->orderBy('created_at', 'asc')->get();
        return $this->sendOkResponse($task);
    }
    public function clientConfirmStatus($id, Request $request)
    {
        $task = Tasks::find($id);
        if($task->status<1){
             return $this->sendFailedResponse('Task chưa được client hoàn thành', -1, 'Task chưa được client hoàn thành', 422);
        }
        $task->confirm_status = $request->confirm_status;
        if($request->confirm_status==1){
            $task->status =2;
        }
        if ($request->confirm_status == 0)
            $task->status = 0;
        $task->save();
        $task = Tasks::find($id);
        $task['comment']=Comment::where('task_id',$id)->orderBy('created_at', 'asc')->get();
        return $this->sendOkResponse($task);
    }

    public function destroyTask($id, Request $request)
    {
        $task = Tasks::find($id);
        $task->destroy();
        return $this->sendOkResponse();
    }
    public function recruitmentConfirmation($id, Request $request)
    {
        $infoApply = CandidateApplyJob::find($id);
        if ($infoApply == null)
            return $this->sendFailedResponse("Không tìm thấy thông tin ứng tuyển với ID đã cung cấp", -1, "Không tìm thấy thông tin ứng tuyển với ID đã cung cấp", 422);
        $ListApply = CandidateApplyJob::where('job_id', $infoApply->job_id)->where('status', '>=', 2)->get();
        if (count($ListApply) > 1) {
            foreach ($ListApply as $apply) {
                $apply->status = 1;
                $apply->save();
            }
            return $this->sendFailedResponse("thông tin lỗi vui lòng thử lại.", -1, "thông tin lỗi vui lòng thử lại.", 422);
        }
        $ListApply = CandidateApplyJob::where('job_id', $infoApply->job_id)->get();
        $Job = Job::find($infoApply->job_id);
        $Job->status = 3;
        $Job->save();
        //dd($ListApply);
        foreach ($ListApply as $apply) {
            if ($apply->id == $id) {

                $apply->status = 3;
                $apply->save();
            } else {
                $apply->status = -1;
                $apply->save();
            }
        }
        return $this->sendOkResponse("ok");
    }

    public function feedBack(Request $request){
        global $user_info;
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        $rq['type_id']=1;//freelancer-feedback-client
        if($user_info->user_type == 'client'){
            $rq['type_id']=2;//client-feedback-freelancer 
        }
        // Validation rules
        $rules = [
            'job_id'=>['required','integer'],
            'user_id'=>['required','integer'],
            'type_id'=>['required','integer'],
            'rate'=>['required'],
            'comment'=>['string']
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
        $info= FeedBacks::where(['job_id' =>$validator['job_id'], 'job_type' =>$validator['job_type'],'user_id' =>$validator['user_id']])->get()->toArray();
        if (count($info)>0) {
            return $this->sendFailedResponse("Thất bại! Công việc này đã được đánh giá", -1, "Thất bại! Công việc này đã được đánh giá", 200);
        } 
        $data = FeedBacks::create($validator);
        return $this->sendOkResponse($data);
    } 
}
