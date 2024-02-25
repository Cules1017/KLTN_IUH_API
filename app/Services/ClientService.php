<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Freelancer;
use App\Models\Job;
use App\Models\Skill;
use Exception;
use Illuminate\Support\Facades\DB;
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

    public function getList($num = 10, $page = 1, $searchKeyword = '', $id, $status = null, $sex = null)
    {
        try {
            // Xây dựng query Eloquent
            $query = Client::query();
            if ($id) {
                $query->where('id', '=', $id);
            } elseif ($searchKeyword !== '') {
                $query->where('username', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('email', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('first_name', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('last_name', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('company_name', 'like', '%' . $searchKeyword . '%');
            }
            if ($status != null)
                $query->where('status', '=',  $status);
            if ($sex != null)
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
                'total_page' => $totalPages,
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
            $admin = Client::findOrFail($id);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function updateAtribute($id, $attribute)
    {
        try {
            $admin = Client::findOrFail($id);
            $admin->update($attribute);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function destroy($id)
    {
        try {
            $admin = Client::findOrFail($id);
            $admin->destroy();
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function autoGetFreelancer($page, $num)
    {


        try {
            global $user_info;
            $currentUserJobs = Job::where('client_id', $user_info->id)->get();
            $relatedSkills = [];
            if($currentUserJobs!=null)
            foreach ($currentUserJobs as $job) {
                $jobSkills = DB::table('skill_job_map')
                    ->where('job_id', $job->id)
                    ->pluck('skill_id')
                    ->toArray();
                $relatedSkills = array_merge($relatedSkills, $jobSkills);
            }
            $relatedSkills = array_unique($relatedSkills);

            // Lấy danh sách freelancer có kỹ năng liên quan
            $freelancers = DB::table('freelancer')
                ->join('skill_freelancer_map', 'freelancer.id', '=', 'skill_freelancer_map.freelancer_id')
                ->whereIn('skill_freelancer_map.skill_id', $relatedSkills)
                ->select('freelancer.*')
                ->distinct()
                ->paginate($num);

            return [
                'data' => $freelancers->items(),
                'total' => $freelancers->total(),
                'total_page' => $freelancers->lastPage(),
                'num' => $num,
                'current_page' => $page,
            ];
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }
    public function searchListFreelancer($page, $num, $keyword, $skills, $date_of_birth, $expected_salary, $sex)
    {
        // hoàn thành giúp tôi
        try {
            // Bắt đầu truy vấn
            $query = DB::table('freelancer');

            // Tìm kiếm theo từ khóa
            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('intro', 'LIKE', "%$keyword%")
                        ->orWhere('address', 'LIKE', "%$keyword%");
                });
            }

            // Tìm kiếm theo kỹ năng
            if (!empty($skills)) {
                $skillIds = explode(',', $skills);
                $query->join('skill_freelancer_map', 'freelancer.id', '=', 'skill_freelancer_map.freelancer_id')
                    ->whereIn('skill_freelancer_map.skill_id', $skillIds);
            }

            // Tìm kiếm theo ngày sinh
            
            if (!empty($date_of_birth)) {
                $deadlineRange = explode(',', $date_of_birth);
                if (count($deadlineRange) === 2) {
                    $query->whereBetween('date_of_birth', [$deadlineRange[0], $deadlineRange[1]]);
                }
            }

            // Tìm kiếm theo mức lương mong đợi
            if (!empty($expected_salary)) {
                $salaries = explode(',', $expected_salary);
                $query->whereBetween('expected_salary', $salaries);
            }

            // Tìm kiếm theo giới tính
            if (!empty($sex)) {
                $query->where('sex', $sex);
            }

            // Thực hiện phân trang
            $freelancers = $query->select('freelancer.*')->distinct()
                ->paginate($num);
            $data=[];
            foreach($freelancers->items()as $freelancer){
                unset($freelancer->password);
                unset($freelancer->email_verified_at);
                $data[] = $freelancer;
            }
            return [
                'data' => $freelancers->items(),
                'total' => $freelancers->total(),
                'total_page' => $freelancers->lastPage(),
                'num' => $num,
                'current_page' => $page,
            ];
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }
}
