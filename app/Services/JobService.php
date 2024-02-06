<?php

namespace App\Services;

use App\Models\Job;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class JobService implements IJobService
{
    public function create($attributes = [])
    {
        try {
            // dd($attributes);
            return Job::create($attributes);
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function getList($num = 10, $page = 1, $searchKeyword = '', $client_info, $min_proposal, $id, $bids, $status)
    {
        try {
            // Xây dựng query Eloquent
            $query = Job::query();
            $query->join('client', 'jobs.client_id', '=', 'client.id');
            if ($id) {
                $query->where('id', '=', $id);
            } elseif ($searchKeyword !== '') {
                $query->where('title', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('desc', 'like', '%' . $searchKeyword . '%');
            }
            if ($client_info !== null) {
                $query->where('username', 'like', '%' . $client_info . '%')
                    ->orWhere('email', 'like', '%' . $client_info . '%');
            }
            if ($min_proposal !== null) {
                $query->where('min_proposal', '>=', $min_proposal);
            }
            if ($status !== null) {
                $query->where('status', '=', $status);
            }
            if (!empty($bids) && is_array($bids)) {
                foreach ($bids as $bid) {
                    if (count($bid) === 2) {
                        $operator = $bid[0];
                        $value = $bid[1];
                        $query->where('bids', $operator, $value);
                    }
                }
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
                'total_page' => $totalPages,
                'num' => $num,
                'current_page' => $page,
            ];
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function getJobByAtribute(array $attributes, array $values, $page = 1, $num = 99999999)
    {
        try {
            $query = Job::query();

            // Kiểm tra số lượng thuộc tính và giá trị có khớp nhau
            if (count($attributes) !== count($values)) {
                // Xử lý lỗi nếu không khớp
                return []; // Hoặc bạn có thể trả về thông báo lỗi hoặc một giá trị khác tùy thuộc vào yêu cầu của bạn
            }

            // Thêm các điều kiện vào truy vấn dựa trên các thuộc tính và giá trị
            foreach ($attributes as $index => $attribute) {
                $query->where($attribute, $values[$index]);
            }

            // Thực hiện truy vấn và lấy kết quả
            $total = $query->count();

            // Thực hiện phân trang và lấy dữ liệu
            $data = $query->skip(($page - 1) * $num)
                ->take($num)
                ->get();
            $totalPages = ceil($total / $num);

            return [
                'data' => $data,
                'total' => $total,
                'total_page' => $totalPages,
                'num' => $num,
                'current_page' => $page,
            ];
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }



    public function updateAtribute($id, $attribute)
    {
        try {
            $admin = Job::findOrFail($id);
            $admin->update($attribute);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function destroy($id)
    {
        try {
            $admin = Job::findOrFail($id);
            Job::destroy($id);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }
}
