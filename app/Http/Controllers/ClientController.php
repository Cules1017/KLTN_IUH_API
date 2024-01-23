<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
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
        $id = $request->id ? $request->id : null;
        $sex= $request->sex ? $request->sex :null;
        $status=$request->status ? $request->status :null;

        $data = $this->clientService->getList($num, $page, $searchValue, $id,$status,$sex);
        return $this->sendOkResponse($data);
    }

    public function update($id, Request $request)
    {
        //case1 update lại khi qua đến client
        global $user_info;
        if ($user_info->id == $id) { //luồng này chạy update bản thân

            $messages = [
                'username.unique' => 'Tên người dùng đã được sử dụng.',
                'email.unique' => 'Email đã được sử dụng.',
            ];
            $validator = Validator::make($request->all(), [
                'username' => ['max:255', Rule::unique('admin')->ignore($id)],
                'email' => ['email', 'max:255', Rule::unique('admin')->ignore($id)],
                'first_name' => ['string', 'max:255'],
                'last_name' => ['string', 'max:255'],
                'phone_num' => ['string', 'max:255'],
                'address' => ['string', 'max:255'],
                'sex' => ['integer', Rule::in(1, 2)],
                'date_of_birth' => ['string', 'max:255'],
                'avatar' =>  [
                    'nullable',
                    'image',
                    'mimes:jpeg,png,jpg,gif',
                    'max:2048'
                ],
            ], $messages);
            if ($validator->fails()) {
                return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
            }
            $validator = $validator->validated();
            $imagePath = null;
            if ($request->hasFile('avatar')) {
                $imagePath = FileHelper::saveImage($request->file('avatar'), 'admin', 'avatar');
            }
            unset($validator['avatar']);
            $data = $this->adminService->updateAtribute($id, array_merge($validator, ['avatar_url' => $imagePath]));
            return $this->sendOkResponse($data);
        } else { //luồng này cho admin update người khác
            if (isset($user_info->position)||!in_array($user_info->position, [1, 2])) {
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
}
