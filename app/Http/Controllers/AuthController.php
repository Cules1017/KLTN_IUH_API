<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Freelancer;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Token;
use App\Helpers\MyHelper;
use Illuminate\Support\Facades\DB;
use App\Events\OtpEvent;
use GuzzleHttp\Client as ClientApi;
use App\Helpers\FileHelper;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userName' => 'required_without:email|string',
            'email' => 'required_without:userName|string|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, null, 422);
        }

        $userType = null;
        $infoLogin = [];
        if (empty($validator->validated()['userName'])) {
            $infoLogin = [
                'email' => $validator->validated()['email'],
                'password' => $validator->validated()['password'],
            ];
        } else {
            $infoLogin =  [
                'username' => $validator->validated()['userName'],
                'password' => $validator->validated()['password'],
            ];
        }
        if (Auth::guard('admin')->attempt($infoLogin))
            $userType = 'admin';
        if (Auth::guard('client')->attempt($infoLogin))
            $userType = 'client';
        if (Auth::guard('freelancer')->attempt($infoLogin))
            $userType = 'freelancer';
        //dd(Auth::guard('client')->attempt($validator->validated()),Auth::guard('admin')->attempt($validator->validated()),!(Auth::guard('client')->attempt($validator->validated())&&Auth::guard('admin')->attempt($validator->validated())));
        if ($userType == null) {
            return $this->sendFailedResponse('Tài khoản hoặc mật khẩu không đúng. Vui lòng kiểm tra và thao tác lại.', -1, null, 200);
        }
        $customClaims = [
            'user_type' => $userType,
            'user_info' => auth($userType)->user(),
            // Add any other additional claims you want to include
        ];
        if (Auth::guard($userType)->user()->email_verified_at == null) {
            return $this->sendFailedResponse('Vui lòng xác thực email trước khi login.', -1, null, 200);
        }
        $token = JWTAuth::customClaims($customClaims)->fromUser(Auth::guard($userType)->user());

        return $this->createNewToken($token, $userType, auth($userType)->user());
    }
    public function redirectToGoogle(Request $request)
    {

        return Socialite::driver('google')->redirect();
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userName' => [
                'required',
                'string',
                'between:2,100',
                Rule::unique('admin')->where(function ($query) {
                    return $query->where('username', request('userName'));
                }),
                Rule::unique('client')->where(function ($query) {
                    return $query->where('username', request('userName'));
                }),
                Rule::unique('freelancer')->where(function ($query) {
                    return $query->where('username', request('userName'));
                }),
            ],
            'email' => 'required|string|email|max:100|unique:admin|unique:client|unique:freelancer',
            'password' => 'required|string|min:6',
            'typeUser' => 'required|string|in:freelancer,admin,client'
        ]);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors()->toJson(), -1, null, 422, $validator->errors());
        }
        $validatedData = collect($validator->validated())->except('typeUser')->all();
        if ($request->typeUser == 'admin') {
            if (!isset($request->serectKey) || $request->serectKey != 'minh_huyen_cute') {
                return $this->sendFailedResponse('Không có quyền tạo tài khoản admin', -1, null, 401);
            }
            $user = Admin::create(array_merge(
                $validatedData,
                [
                    'password'       => bcrypt($request->password),
                    'first_name'        => isset($request->firstName) ? $request->firstName : '',
                    'last_name'         => isset($request->lastName) ? $request->lastName : '',
                    'date_of_birth'         => isset($request->dateOfBirth) ? $request->dateOfBirth : null,
                    'phone_num'         => isset($request->phoneNum) ? $request->phoneNum : null,
                    'sex'            => isset($request->sex) ? $request->sex : 0,
                    'position'            => isset($request->position) ? $request->position : 1,
                    'intro'             => isset($request->intro) ? $request->intro : null,
                    'address'           => isset($request->address) ? $request->address : null,

                ]
            ));

            $token = Auth::guard('admin')->attempt($validatedData);
        } elseif ($request->typeUser == 'freelancer') {
            $user = Freelancer::create(array_merge(
                $validatedData,
                [
                    'password'       => bcrypt($request->password),
                    'first_name'        => isset($request->firstName) ? $request->firstName : '',
                    'last_name'         => isset($request->lastName) ? $request->lastName : '',
                    'date_of_birth'         => isset($request->dateOfBirth) ? $request->dateOfBirth : null,
                    'phone_num'         => isset($request->phoneNum) ? $request->phoneNum : null,
                    'sex'            => isset($request->sex) ? $request->sex : 0,
                    'intro'             => isset($request->intro) ? $request->intro : null,
                    'address'           => isset($request->address) ? $request->address : null,
                    'expected_salary'   => isset($request->expectedSalary) ? $request->expectedSalary : null,

                ]

            ));

            $token = Auth::guard('freelancer')->attempt($validatedData);
        } elseif ($request->typeUser == 'client') {
            $user = Client::create(array_merge(
                $validatedData,
                [
                    'password' => bcrypt($request->password),
                    'first_name' => isset($request->firstName) ? $request->firstName : '',
                    'last_name' => isset($request->lastName) ? $request->lastName : '',
                    'phone_num' => isset($request->phoneNum) ? $request->phoneNum : null,
                    'sex'            => isset($request->sex) ? $request->sex : 0,
                    'company_name' => isset($request->companyName) ? $request->companyName : null,
                    'introduce' => isset($request->introduce) ? $request->introduce : null,
                    'address' => isset($request->address) ? $request->address : null,
                    'date_of_birth'         => isset($request->dateOfBirth) ? $request->dateOfBirth : null,
                ]

            ));

            $token = Auth::guard('client')->attempt($validatedData);
        }
        $requestEmail = $request->email;
        $nameUser = $request->userName;

        $customClaims = [
            //'user_type' => $userType,
            'user_info' => [
                'email' => $requestEmail,
                'username' => $nameUser
            ],
            // Add any other additional claims you want to include
        ];
        $token = JWTAuth::customClaims($customClaims)->fromUser(Auth::guard($request->typeUser)->user());
        Mail::send('mailfb', array('name' => 'aaaa', 'email' => $requestEmail, 'token' => $token, 'content' => 'aaa'), function ($message) use ($requestEmail, $nameUser, $token) {
            $message->to($requestEmail, $nameUser)->subject('Hi Mai ăn sáng hog bà!');
        });
        return $this->sendOkResponse([
            'message' => 'Tạo tài khoản thành công!!!. Vui lòng xác thực email để tiếp tục.',
            'user' => $user,
        ], 'Tạo tài khoản thành công!!!. Vui lòng xác thực email để tiếp tục.');
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return $this->sendOkResponse([], 'User successfully signed out');
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }

    public function verifyCodeEmail(Request $request)
    {
        $token = new Token($request->token);
        //$token = JWTAuth::getTokenizer()->parse($request->token);

        $apy = JWTAuth::decode($token);
        $user = Admin::where('email', $apy['user_info']['email'])->first();
        if ($user == null) $user = Freelancer::where('email', $apy['user_info']['email'])->first();
        if ($user == null) $user = Client::where('email', $apy['user_info']['email'])->first();
        if ($user) {
            // Update the existing user instance
            $user->email_verified_at = now();
            $user->save();
        }
        return  redirect(env('FRONTEND_URL'));
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token, $typeUser = 'admin', $userInfo = null)
    {
        if($typeUser=='freelancer')
        $userInfo->skill=DB::table('skills')->select('skills.*')
                ->join('skill_freelancer_map', 'skills.id', '=', 'skill_freelancer_map.skill_id')->where('skill_freelancer_map.freelancer_id',"=",$userInfo->id)->get()->toArray();

        return $this->sendOkResponse([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth($typeUser)->factory()->getTTL() * 6000*30000,
            'user_type' => $typeUser,
            'user' => $userInfo,
        ]);
    }

    public function changePassWord(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors()->toJson());
        }
        $userId = auth()->user()->id;

        $user = Admin::where('id', $userId)->update(
            ['password' => bcrypt($request->new_password)]
        );
        return $this->sendOkResponse([
            'message' => 'User successfully changed password',
            'user' => $user,
        ], 'User successfully changed password');
    }

    public function handleGoogleCallback()

    {
        try {

            $user = Socialite::driver('google')->stateless()->user();
            $finduser = Freelancer::where('google_id', $user->id)->first();
            $userExist = Freelancer::where('email', $user->email)->first();

            if ($finduser) {
                $customClaims = [
                    'user_type' => 'freelancer',
                    'user_info' => $finduser,
                    // Add any other additional claims you want to include
                ];
                $token = JWTAuth::customClaims($customClaims)->fromUser($finduser);
                auth('freelancer')->factory()->getTTL() * 600000*30000;
                return redirect(env('FRONTEND_URL') . 'auth/google?token=' . $token);
            } else {
                if ($userExist) {
                    return response()->json([
                        'message' => 'Email account has been registered on the system. Please use another email.'
                    ], 400);
                }
                $newUser = Freelancer::create([
                    'username' => $user->name,
                    'email' => $user->email,
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'google_id' => $user->id

                ]);

                $customClaims = [
                    'user_type' => 'freelancer',
                    'user_info' => $newUser,
                    // Add any other additional claims you want to include
                ];
                $token = JWTAuth::customClaims($customClaims)->fromUser($newUser);
                auth('freelancer')->factory()->getTTL() * 600000*30000;
                //return $this->createNewToken($token);

                return redirect(env('FRONTEND_URL') . 'auth/google?token=' . $token);
            }
        } catch (\Exception $e) {
            return redirect(env('FRONTEND_URL') . 'login');
        }
    }

    public function getInfoUserById(Request $request){
        $rq = MyHelper::convertKeysToSnakeCase($request->all());
        $validator = Validator::make($rq, [
            'id'=>'required',
            'type_user' => 'required|string|in:freelancer,admin,client'
        ]);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors()->toJson(), -1, null, 422, $validator->errors());
        }
        $dataResult=null;
        $validator = $validator->validated();
        if($validator['type_user']=='admin'){
            $dataResult['base_info']= Admin::find($validator['id']);
            
        }
        elseif($validator['type_user']=='client'){
            $dataResult['base_info']=Client::find($validator['id']);
            $dataResult['job']=DB::select('select * FROM jobs WHERE client_id ='.$validator['id']);
        }
        else{
            $dataResult['base_info']=Freelancer::find($validator['id']);
            $dataResult['job']=DB::select('select a.status as status_apply,b.* FROM candidate_apply_job a, jobs b WHERE a.job_id = b.id AND a.freelancer_id ='.$validator['id']);
            $dataResult['experience']= DB::select('SELECT DISTINCT k.id, k.name '.
            'FROM skill_job_map sm, skills k '.
            'WHERE sm.skill_id= k.id AND sm.job_id IN('.
            'select b.id FROM candidate_apply_job a, jobs b WHERE a.job_id = b.id AND a.freelancer_id ='.$validator['id'].')'
        );   
        }
        if($dataResult==null) return $this->sendBadRequestResponse('không tìm thấy thông tin user');
        return $this->sendOkResponse($dataResult);
    }

    public function sendOtp(){
        global $user_info;
        $otp = "";
        for ($i = 0; $i < 6; $i++) {
            $otp .= rand(0, 9);
        }
        
        Mail::send('mailnoti', ['title' => "MÃ XÁC THỰC",'message_mail'=>'Mã xác thực của bạn là: '.$otp.'. Mã xác thực của bạn sẽ có hiệu lực trong 3 phút.'], function ($message) use ($user_info) {
            $message->to($user_info->email, $user_info->first_name)->subject("MÃ XÁC THỰC");
        });
        Session::put('otp', $otp);
        Session::put('otp_expiry', strtotime('+3 minutes', time()));
        event(new OtpEvent($otp,strtotime('+3 minutes', time()),$user_info->id,$user_info->user_type));
        return $this->sendOkResponse([],'Đã gởi mã xác thực qua email!');
    }
    public function verifyOtp(Request $request){
        global $user_info;
        $otp = $request->input('otp');
        $storedOTP = Session::get('otp');
        $expiryTime = Session::get('otp_expiry');
        if ($expiryTime>=time() && $otp == $storedOTP) {
            return $this->sendOkResponse('Mã OTP hợp lệ.','Mã OTP hợp lệ.',0);
        } else {
            if($otp != $storedOTP){
                return $this->sendOkResponse('Mã OTP không hợp lệ. Vui lòng kiểm tra lại hoặc chọn gởi OTP để nhận mã mới.','Mã OTP không hợp lệ. Vui lòng kiểm tra lại hoặc chọn gởi OTP để nhận mã mới.',-1);
            }else{
                return $this->sendOkResponse('Mã OTP hết hạn.Vui lòng chọn gởi OTP để nhận mã mới.','Mã OTP hết hạn. Vui lòng chọn gởi OTP để nhận mã mới',-1);
            }
        }
        
        
        return $this->sendOkResponse([],'Đã gởi mã xác thực qua email!');
    }


    public function CitizenIdentificationCardVerify(Request $request){
        global $user_info;
        $user_id = $user_info->id;
        $user_type= $user_info->user_type;
        $imagePath='';
        if ($request->hasFile('image')) {
            $imagePath = FileHelper::saveImage($request->file('image'), 'CitizenIdentificationCard', 'avatar');
        }
        else{
            return $this->sendFailedResponse('Vui lòng truyền lên hình ảnh căn cước công dân', -1, null, 422,'Vui lòng truyền lên hình ảnh căn cước công dân');
        }
        try{
            $client = new ClientApi();
    
            $response = $client->request('POST', 'https://api.fpt.ai/vision/idr/vnm', [
                'headers' => [
                    'api-key' => 'Ns7sD8TTu1kPNx9Em287lSANE69ce2qR',
                ],
                'multipart' => [
                    [
                        'name' => 'image',
                        'contents' => fopen($imagePath, 'r'),
                    ],
                ],
            ]);
            $resultCallApi=json_decode($response->getBody()->getContents());
        
            $dataInsert=[
                //"address"=>$resultCallApi->data[0]->address,
                "date_of_birth"=>\Carbon\Carbon::createFromFormat('d/m/Y',$resultCallApi->data[0]->dob )->toDateString(),
                "citizen_identification_id"=>$resultCallApi->data[0]->id,
                "citizen_identification_url"=>$imagePath,
                "sex"=>$resultCallApi->data[0]->sex=="NỮ"?1:0,
                "is_completed_profile"=>1,
            ];
        }catch(\Exception $e){
             return $this->sendFailedResponse('Có Lỗi Khi Xác Thực Vui Lòng Kiểm Tra Lại Hình Ảnh CCCD Của Bạn.', -1, null, 400,'Có Lỗi Khi Xác Thực Vui Lòng Kiểm Tra Lại Hình Ảnh CCCD Của Bạn.');
        }
        

        $userUpdate=null;
        if($user_type=='freelancer'){
            $userUpdate=Freelancer::find($user_id)->update($dataInsert);
            $userUpdate=Freelancer::find($user_id);
        }
        else{
            $userUpdate=Client::find($user_id)->update($dataInsert);
            $userUpdate=Client::find($user_id);
        }
        return $this->sendOkResponse($userUpdate,'Xác thực thông tin thành công!');
    }
}
