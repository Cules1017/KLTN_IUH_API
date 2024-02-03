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
            'email' => 'required_without:username|string|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, null, 422);
        }
        $userType = null;
        if (Auth::guard('admin')->attempt($validator->validated()))
            $userType = 'admin';
        if (Auth::guard('client')->attempt($validator->validated()))
            $userType = 'client';
        //dd(Auth::guard('client')->attempt($validator->validated()),Auth::guard('admin')->attempt($validator->validated()),!(Auth::guard('client')->attempt($validator->validated())&&Auth::guard('admin')->attempt($validator->validated())));
        if ($userType == null) {
            return $this->sendFailedResponse('Unauthorized', -1, null, 401);
        }
        $customClaims = [
            'user_type' => $userType,
            'user_info' => auth($userType)->user(),
            // Add any other additional claims you want to include
        ];
        if (Auth::guard($userType)->user()->email_verified_at == null) {
            return $this->sendFailedResponse('Vui lòng xác thực email trước khi login.', -1, null, 401);
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
                    'sex'            => isset($request->sex) ? $request->sex : 'Không xác định',
                    'position'            => isset($request->position)?$request->position:1,
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
                    'sex'            => isset($request->sex) ? $request->sex : 'Không xác định',
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
                    'sex'            => isset($request->sex) ? $request->sex : 'Không xác định',
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
        return $this->sendOkResponse([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth($typeUser)->factory()->getTTL() * 60,
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
                auth('freelancer')->factory()->getTTL() * 60;
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
                auth('freelancer')->factory()->getTTL() * 60;
                //return $this->createNewToken($token);

                return redirect(env('FRONTEND_URL') . 'auth/google?token=' . $token);
            }
        } catch (\Exception $e) {
            return redirect(env('FRONTEND_URL') . 'login');
        }
    }
}
