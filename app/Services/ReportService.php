<?php

namespace App\Services;

use App\Models\Report;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class ReportService implements IReportService
{
    public function create($attributes = [])
    {
        try {
           // dd($attributes);
            return Report::create($attributes);
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function getList($num=10,$page=1,$type_result=null,$id)
    {
        try {
        // Xây dựng query Eloquent
        $query = Report::query();
        if($id){
            $query->where('id','=',$id);
        }
        elseif ($type_result!==null) {
            $query->where('type_id', '=',  $type_result );
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
            $admin=Report::findOrFail($id);
            $admin->update($attribute);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function destroy($id){
        try {
            $admin=Report::findOrFail($id);
            Report::destroy($id);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }


}