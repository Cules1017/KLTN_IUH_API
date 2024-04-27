<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Services\INotificationService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Freelancer;
use App\Models\Notifications;

class NotificationController extends Controller
{
    public $notiService;
    public function __construct(INotificationService $notiService)
    {
        $this->notiService = $notiService;
    }
    public function index(Request $request){
        
        $data=$this->notiService->getMyNotifications();
        return $this->sendOkResponse($data);
    }

    public function seen($id){
        $noti=Notifications::find($id);
        $noti->is_read=1;
        $noti->save();
        return $this->sendOkResponse($noti);
    }
    public function store(Request $request){
        $rules = [
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
            'image' => ['string', 'nullable', 'regex:/^(http(s)?:\/\/.*\.(png|jpg|jpeg|gif|bmp))$/i'],
            'linkable' => ['required','string'],
            'user_type'=>['required','string'],
            'user_id'=>['required'],
        ];
        $messages = [
            'required' => 'Trường :attribute là bắt buộc.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated(); 
        if ($request->hasFile('imagefile')) {
            $imagePath = FileHelper::saveImage($request->file('imagefile'), 'noti', 'noti_image');
            $validator['image'] = $imagePath;
        }
        $user_info=null;
        if($request->user_type=='client'){
            $user_info=Client::find($request->user_id);
        }elseif($request->user_type=='freelancer'){
            $user_info=Freelancer::find($request->user_id);
        }else{
            $user_info=Admin::find($request->user_id);
        }
        if($user_info==null) return $this->sendFailedResponse("Không tồn tại user push noti");
        unset($validator['user_id']);
        unset($validator['user_type']);

        $user_info->user_type=$request->user_type;
        $data = $this->notiService->pushNotitoUser($user_info,$validator,$request->smail);
        return $this->sendOkResponse($data);
    }

    public function update($id,Request $request){
        $data=$this->notiService->updateAtribute($id,["is_read"=>1]);
        return $this->sendOkResponse($data);
    }
    
}