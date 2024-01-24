<?php

namespace App\Services;

use App\Models\SystermConfig;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class SystermConfigService implements ISystermConfigService
{
    public function create($attributes = [])
    {
        try {
           // dd($attributes);
            return SystermConfig::create($attributes);
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function getList($num=10,$page=1,$searchKeyword='',$id)
    {
        try {
        // Xây dựng query Eloquent
        $query = SystermConfig::query();
        if($id){
            $query->where('id','=',$id);
        }
        elseif ($searchKeyword!=='') {
            $query->where('key', 'like', '%' . $searchKeyword . '%');
        }
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

    

    public function updateAtribute($id,$attribute){
        try {
            $admin=SystermConfig::findOrFail($id);
            $admin->update($attribute);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function destroy($id){
        try {
            $admin=SystermConfig::findOrFail($id);
            SystermConfig::destroy($id);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }


}