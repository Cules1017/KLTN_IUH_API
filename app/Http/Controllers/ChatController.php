<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\NewChatEvent;
use App\Helpers\FileHelper;
use App\Helpers\MyHelper;
use App\Models\ChatRooms;
use App\Models\Client;
use App\Models\Freelancer;
use App\Models\Messages;
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

class ChatController extends Controller
{
    public function createNewRoomChat(Request $request)
    {
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        $rules = [
            'freelancer_id' => [
                'required',
                'integer',
                Rule::exists('freelancer', 'id'),
            ],
            'client_id' => [
                'required',
                'string',
                Rule::exists('client', 'id'),
            ],
        ];
        $validator = Validator::make($rq, $rules);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $counts = DB::table('chat_rooms')
            ->select('client_id', 'freelancer_id', DB::raw('COUNT(*) as count'))
            ->groupBy('client_id', 'freelancer_id')
            ->get()->toArray();
        if (count($counts) > 0 && $counts[0]->count > 0) {
            return $this->sendBadRequestResponse("Phòng đã tồn tại");
        }
        $validator = $validator->validated();
        // Tạo một bản ghi mới trong bảng chat_rooms
        $roomId = DB::table('chat_rooms')->insertGetId(array_merge([
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $validator));
        broadcast(new NewChatEvent(ChatRooms::find($roomId), $validator['freelancer_id'], 'f'))->toOthers();
        broadcast(new NewChatEvent(ChatRooms::find($roomId), $validator['client_id'], 'c'))->toOthers();
        // Kiểm tra nếu tạo phòng chat thành công
        if ($roomId) {
            return $this->sendOkResponse(ChatRooms::find($roomId), "Tạo Phòng chat thành công.");
        } else {
            return $this->sendBadRequestResponse("Tạo phòng chat thất bại vui lòng thử lại!!!");
        }
    }
    public function getMyChat()
    {
        global $user_info;
        $id = $user_info->id;
        $user_type = $user_info->user_type;

        $listChat = ChatRooms::where($user_type . '_id', '=', $id)->get();
        // Mảng kết quả
        $result = [];

        // Lặp qua từng cuộc trò chuyện
        foreach ($listChat as $chat) {
            // Lấy tin nhắn cuối cùng của cuộc trò chuyện
            $lastMessage = Messages::where('room_id', $chat->id)->latest()->first();
            $opponetInfo = $user_type == 'client' ? Freelancer::select('id', 'username', 'email', 'first_name', 'last_name', 'avatar_url')->where('id', $chat->freelancer_id)->get() : Client::select('id', 'username', 'email', 'first_name', 'last_name', 'avatar_url')->where('id', $chat->client_id)->get();

            // Nếu tồn tại tin nhắn cuối cùng
            if ($lastMessage) {
                // Thêm thông tin vào mảng kết quả
                $result[] = array_merge($chat->toArray(), [
                    'last_message' => $lastMessage->content,
                    'last_message_time' => $lastMessage->created_at,
                    'opponent' => $opponetInfo->toArray()
                ]);
            } else {
                $result[] = array_merge($chat->toArray(), [
                    'last_message' => "Hãy nhắn gì đó để bắt đầu cuộc trò chuyện",
                    'last_message_time' => now(),
                    'opponent' => $opponetInfo->toArray()
                ]);
            }
        }
        return $this->sendOkResponse($result);
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
