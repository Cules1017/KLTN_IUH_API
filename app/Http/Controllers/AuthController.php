<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Freelancer;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
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
    public function __construct() {
        //$this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
    	$validator = Validator::make($request->all(), [
            'name' => 'required',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $userType=null;
        if(Auth::guard('admin')->attempt($validator->validated()))
            $userType='admin';
        if(Auth::guard('client')->attempt($validator->validated()))
            $userType='client';
        //dd(Auth::guard('client')->attempt($validator->validated()),Auth::guard('admin')->attempt($validator->validated()),!(Auth::guard('client')->attempt($validator->validated())&&Auth::guard('admin')->attempt($validator->validated())));
        if ($userType==null) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $customClaims = [
            'user_type' => $userType,
            'user_info' =>auth($userType)->user() ,
            // Add any other additional claims you want to include
        ];
        $token = JWTAuth::customClaims($customClaims)->fromUser(Auth::guard($userType)->user());

        return $this->createNewToken($token,$userType,auth($userType)->user());
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
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:admin',
            'password' => 'required|string|min:6',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = Admin::create(array_merge(
                    $validator->validated(),
                    ['password' => bcrypt($request->password)]
                ));
        $requestEmail = $request->email;
        $nameUser = $request->name;
        $token=Auth::guard('admin')->attempt($validator->validated());
        $customClaims = [
            //'user_type' => $userType,
            'user_info' =>[
                'email'=>$requestEmail,
                'name'=>$nameUser
            ],
            // Add any other additional claims you want to include
        ];
        $token = JWTAuth::customClaims($customClaims)->fromUser(Auth::guard('admin')->user());
        Mail::send('mailfb', array('name'=>'aaaa','email'=>$requestEmail,'token'=>$token, 'content'=>'aaa'), function($message)use ($requestEmail,$nameUser,$token){
	        $message->to($requestEmail, $nameUser)->subject('Hi Mai ăn sáng hog bà!');
	    });
        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json(auth()->user());
    }

    public function verifyCodeEmail(Request $request) {
        $token = new Token($request->token);
        //$token = JWTAuth::getTokenizer()->parse($request->token);

        $apy = JWTAuth::decode($token);
        $user=Admin::where('email',$apy['user_info']['email'])->first();
        if($user){
           // Update the existing user instance
            $user->email_verified_at = now();
            $user->save();
        }
     return  redirect('https://www.google.com/');
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token,$typeUser='admin',$userInfo=null){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth($typeUser)->factory()->getTTL() * 60,
            'user' => $userInfo,
        ]);
    }

    public function changePassWord(Request $request) {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|confirmed|min:6',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        $userId = auth()->user()->id;

        $user = Admin::where('id', $userId)->update(
                    ['password' => bcrypt($request->new_password)]
                );

        return response()->json([
            'message' => 'User successfully changed password',
            'user' => $user,
        ], 201);
    }
    
    public function handleGoogleCallback()

    {
        try {

            $user =Socialite::driver('google')->stateless()->user();
            $finduser = Freelancer::where('google_id', $user->id)->first();
            $userExist = Freelancer::where('email', $user->email)->first();
            
            if($finduser){
                $customClaims = [
                    'user_type' =>'freelancer',
                    'user_info' =>$finduser,
                    // Add any other additional claims you want to include
                ];
                $token = JWTAuth::customClaims($customClaims)->fromUser($finduser);
                auth('freelancer')->factory()->getTTL() * 60;
                return redirect(env('FRONTEND_URL').'auth/google?token='.$token);
            }else{
                if($userExist){
                    return response()->json([
                        'message' => 'Email account has been registered on the system. Please use another email.'
                    ], 400);
                }
                $newUser = Freelancer::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' =>bcrypt('password'),
                    'email_verified_at'=>now(),
                    'google_id'=> $user->id

                ]);

                $customClaims = [
                    'user_type' =>'freelancer',
                    'user_info' =>$newUser ,
                    // Add any other additional claims you want to include
                ];
                $token = JWTAuth::customClaims($customClaims)->fromUser($newUser);
                auth('freelancer')->factory()->getTTL() * 60;
                //return $this->createNewToken($token);

                return redirect(env('FRONTEND_URL').'auth/google?token='.$token);

            }
        } catch (\Exception $e) {
            return redirect(env('FRONTEND_URL').'login');

        }

    }
}
