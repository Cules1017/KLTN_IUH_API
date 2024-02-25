<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Services\INotificationService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
    public function store(Request $request){
        $rules = [
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
            'time_push'=> ['string'],
            'image' => ['string', 'nullable', 'regex:/^(http(s)?:\/\/.*\.(png|jpg|jpeg|gif|bmp))$/i'],
            'linkable' => ['required','string'],
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
        $data = $this->notiService->createNoti($validator,$request->smail);
        return $this->sendOkResponse($data);
    }

    public function update($id,Request $request){
        $data=$this->notiService->updateAtribute($id,["is_read"=>1]);
        return $this->sendOkResponse($data);
    }
    
}