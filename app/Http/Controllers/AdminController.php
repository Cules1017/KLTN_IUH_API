<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Services\IAdminService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminController extends Controller
{
    public $adminService;
    public function __construct(IAdminService $adminService)
    {
        $this->adminService = $adminService;
    }
    public function index(Request $request)
    {
        $num = $request->num ? $request->num : 10;
        $page = $request->page ? $request->page : 1;
        $searchValue = $request->search ? $request->search : '';
        $id = $request->id ? $request->id : null;

        $data = $this->adminService->getList($num, $page, $searchValue, $id);
        return $this->sendOkResponse($data);
    }

    public function store(Request $request)
    {
        global $user_info;
        if (!in_array($user_info->position, [1, 2])) {
            return $this->sendFailedResponse("Không có quyền thao tác", -5, null, 403);
        }
        // Validation rules
        $rules = [
            'position' => ['required', Rule::in(1, 2, 3, 4)], //[1=>'Administrators', 2=>'Moderating editor', 3=>'Customer care',4=>'Partner']
            'username' => ['required',  'max:255', Rule::unique('admin')],
            'email' => ['required', 'email', 'max:255', Rule::unique('admin')],
            'first_name' => ['string', 'max:255'],
            'last_name' => ['string', 'max:255'],
            'phone_num' => ['string', 'max:255'],
            'address' => ['string', 'max:255'],
            'sex' => ['integer', Rule::in(1, 2)],
            'date_of_birth' => ['string', 'max:255'],
        ];

        // Custom error messages
        $messages = [
            'username.unique' => 'Tên người dùng đã được sử dụng.',
            'email.unique' => 'Email đã được sử dụng.',
            'position.in' => 'Chức vụ không hợp lệ.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ];

        // Validate the request
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $validator = $validator->validated();
        $data = $this->adminService->create(
            array_merge(
                $validator,
                ['password' => bcrypt('abc123'), 'status' => 1, "email_verified_at" => now()]
            ) //password defaults
        );
        $customClaims = [
            //'user_type' => $userType,
            'user_info' => [
                'email' => $request->email,
                'username' => $request->username,
            ],
            // Add any other additional claims you want to include
        ];
        $requestEmail = $request->email;
        $nameUser = $request->username;
        $token = JWTAuth::customClaims($customClaims)->fromUser(Auth::guard('admin')->user());
        Mail::send('mailfb', array('name' => 'aaaa', 'email' => $requestEmail, 'token' => $token, 'content' => 'aaa'), function ($message) use ($requestEmail, $nameUser, $token) {
            $message->to($requestEmail, $nameUser)->subject('Hi Mai ăn sáng hog bà!');
        });
        return $this->sendOkResponse($data);
    }

    public function update($id, Request $request)
    {
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
            if (!in_array($user_info->position, [1, 2])) {
                return $this->sendFailedResponse("Không có quyền thao tác", -5, null, 403);
            }
            // Validation rules
            $rules = [
                'position' => [Rule::in([1, 2, 3, 4])], //[1=>'Administrators', 2=>'Moderating editor', 3=>'Customer care',4=>'Partner']
                'status' => [Rule::in([0, 1])], // 0-> trạng thái khóa, 1- trạng thái hoạt động
            ];

            // Custom error messages
            $messages = [
                'position.in' => 'Chức vụ không hợp lệ.',
                'status.in' => 'Trạng thái không hợp lệ.',
            ];
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
            }
            $validator = $validator->validated();
            $data = $this->adminService->update($id, $request->position, $request->status);
            //dd($data);
            if ($data['status'] >= 0) {
                return $this->sendOkResponse($data['data']);
            }
            return $this->sendFailedResponse($data['message'], $data['status'], null, $data['statusCode']);
        }
    }
    public function destroy($id)
    {
        global $user_info;
        if (!in_array($user_info->position, [1])) {
            return $this->sendFailedResponse("Không có quyền thao tác", -5, null, 403);
        }
        $this->destroy($id);
        return $this->sendOkResponse();
    }
}
