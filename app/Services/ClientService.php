<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Skill;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class ClientService implements IClientService
{
    public function create($attributes = [])
    {
        try {
           // dd($attributes);
            return Skill::create($attributes);
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function getList($num=10,$page=1,$searchKeyword='',$id,$status=null,$sex=null)
    {
        try {
        // Xây dựng query Eloquent
        $query = Client::query();
        if($id){
            $query->where('id','=',$id);
        }
        elseif ($searchKeyword!=='') {
            $query->where('username', 'like', '%' . $searchKeyword . '%')
            ->orWhere('email', 'like', '%' . $searchKeyword . '%')
            ->orWhere('first_name', 'like', '%' . $searchKeyword . '%')
            ->orWhere('last_name', 'like', '%' . $searchKeyword . '%')
            ->orWhere('company_name', 'like', '%' . $searchKeyword . '%');
        }
        if($status!=null)
            $query->where('status', '=',  $status);
        if($sex!=null)
            $query->where('sex', '=',  $sex);
        // Lấy tổng số admin
        $total = $query->count();

        // Thực hiện phân trang và lấy dữ liệu
        $data = $query->skip(($page - 1) * $num)
                       ->take($num)
                       ->get();
        $totalPages = ceil($total / $num);
        // Trả về dữ liệu theo định dạng mong muốn (ví dụ: JSON)
        return [
            'data' => $data,
            'total' => $total,
            'total_page'=>$totalPages,
            'num' => $num,
            'current_page' => $page,
        ];
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function getById($id)
    {
        try {
            $admin=Client::findOrFail($id);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function updateAtribute($id,$attribute){
        try {
            $admin=Client::findOrFail($id);
            $admin->update($attribute);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function destroy($id){
        try {
            $admin=Client::findOrFail($id);
            $admin->destroy();
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }


}