<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Helpers\FileHelper;
use App\Helpers\MyHelper;
use App\Models\Messages;
use App\Services\IAdminService;
use App\Services\IJobService;
use App\Services\ISystermConfigService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChatController extends Controller
{
    public function createNewRoomChat()
    {
    }
    public function getMyChat()
    {
    }
    public function getMessagesByRoomId($roomId)
    {
        // Sử dụng Eloquent để lấy 100 tin nhắn theo room_id và sắp xếp theo thời gian tạo
        $messages = Messages::where('room_id', $roomId)
            ->orderBy('created_at', 'asc')
            ->take(100)
            ->get();

        return $messages;
    }
    public function sendMessage(Request $request)
    {
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        $rules = [
            'room_id' => 'required|integer',
            'content' => 'required|string',
            'type_msg' => 'required|in:fc,cf',
        ];
        $validator = Validator::make($rq, $rules);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated();
        $message = Messages::create(array_merge($validator, ['status' => 1]));

        event(new MessageSent($message)); // Phát sự kiện tin nhắn được gửi

        return $this->sendOkResponse($message, 'Đã gởi tin nhắn');
    }
}
