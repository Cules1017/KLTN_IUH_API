<?php

namespace App\Http\Controllers;

use App\Services\IAdminService;
use App\Services\ISkillService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class SkillController extends Controller
{
    public $skillService;
    public function __construct(ISkillService $skillService)
    {
        $this->skillService = $skillService;
    }
    public function index(Request $request){
        $num = $request->num ? $request->num : 10;
        $page = $request->page ? $request->page : 1;
        $searchValue = $request->search ? $request->search : '';
        $id = $request->id ? $request->id : null;

        $data = $this->skillService->getList($num, $page, $searchValue, $id);
        return $this->sendOkResponse($data);
    }
    public function store(Request $request){
        $rules = [
            'name' => ['required',  'max:255', Rule::unique('skills')],
        ];
        $messages = [
            'name' => 'Kỹ năng này đã tồn tại.'
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $data = $this->skillService->create($request->all());
        return $this->sendOkResponse($data);
    }
    public function update($id, Request $request)
    {
        $rules = [
            'name' => ['required',  'max:255', Rule::unique('skills')->ignore($id)],
        ];
        $messages = [
            'name' => 'Kỹ năng này đã tồn tại.'
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendFailedResponse($validator->errors(), -1, $validator->errors(), 422);
        }
        $data = $this->skillService->updateAtribute($id,$request->all());
        return $this->sendOkResponse($data);
    }
}