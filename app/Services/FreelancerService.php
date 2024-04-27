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

class FreelancerService implements IFreelancerService
{
    public function create($attributes = [])
    {
        try {
            // dd($attributes);
            return Freelancer::create($attributes);
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function getList($num = 10, $page = 1, $searchKeyword = '', $id, $status = null, $sex = null)
    {
        try {
            // Xây dựng query Eloquent
            $query = Freelancer::query();
            if ($id) {
                $query->where('id', '=', $id);
            } elseif ($searchKeyword !== '') {
                $query->where('username', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('email', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('first_name', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('last_name', 'like', '%' . $searchKeyword . '%');
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



    public function updateAtribute($id, $attribute)
    {
        try {
            $admin = Freelancer::findOrFail($id);
            $skill = $attribute['skill'];
            $major=$attribute['majors'];
            unset($attribute['skill']);
            unset($attribute['majors']);

            //xử lý thêm skill
            if ($skill && count($skill) > 0) {
                DB::table('skill_freelancer_map')->where('freelancer_id', $admin->id)->delete();
                foreach ($skill as $i) {
                    $tmp = DB::table('skill_freelancer_map')->insert([
                        'freelancer_id' => $admin->id,
                        'skill_id' => $i
                    ]);
                }
            }
            $majorIds = $major?explode(',', $major):[];
            if(count($majorIds)>0)DB::table('major_freelancer_map')->where('freelancer_id', $admin->id)->delete();
            foreach ($majorIds as $majorId) {
                $tmp = DB::table('major_freelancer_map')->insert([
                    'freelancer_id' => $admin->id,
                    'major_id' => $majorId,
                ]);
            }


            if(count($attribute)>0)
                $admin->update($attribute);

            $result=Freelancer::find($id)->toArray();
            $result['skills'] = DB::table('skill_freelancer_map')
                ->join('skills', 'skill_freelancer_map.skill_id', '=', 'skills.id')
                ->where('skill_freelancer_map.freelancer_id', '=', $admin->id)
                ->select('skills.id as skill_id', 'skills.desc as skill_desc', 'skills.name as skill_name')
                ->get();
            $result['majors'] = DB::table('major_freelancer_map')
                ->join('majors', 'major_freelancer_map.major_id', '=', 'majors.id')
                ->where('major_freelancer_map.freelancer_id', '=', $admin->id)
                ->select('majors.id as major_id','majors.title_major')
                ->get();
            return  $result;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function destroy($id)
    {
        try {
            $admin = Freelancer::findOrFail($id);
            Freelancer::destroy($id);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function getById($id)
    {
        try {
            $admin = Freelancer::findOrFail($id);
            $admin['skills'] = DB::table('skill_freelancer_map')
                ->join('skills', 'skill_freelancer_map.skill_id', '=', 'skills.id')
                ->where('skill_freelancer_map.freelancer_id', '=', $admin->id)
                ->select('skills.id as skill_id', 'skills.desc as skill_desc', 'skills.name as skill_name')
                ->get();
            $admin['majors'] = DB::table('major_freelancer_map')
                ->join('majors', 'major_freelancer_map.major_id', '=', 'majors.id')
                ->where('major_freelancer_map.freelancer_id', '=', $admin->id)
                ->select('majors.id as major_id','majors.title_major')
                ->get();
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
    public function searchListFreelancer($page, $num, $keyword, $skills,$majors, $date_of_birth, $sex)
    {
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

            if (!empty($majors)) {
                $$majorIds = explode(',', $majors);
                $query->join('major_freelancer_map', 'freelancer.id', '=', 'major_freelancer_map.freelancer_id')
                    ->whereIn('major_freelancer_map.major_id', $skillIds);
            }

            // Tìm kiếm theo ngày sinh
            
            if (!empty($date_of_birth)) {
                $deadlineRange = explode(',', $date_of_birth);
                if (count($deadlineRange) === 2) {
                    $query->whereBetween('date_of_birth', [$deadlineRange[0], $deadlineRange[1]]);
                }
            }

            // Tìm kiếm theo giới tính
            if (!empty($sex)) {
                $query->where('sex', $sex);
            }

            // Thực hiện phân trang
            $freelancers = $query->select('freelancer.*')->distinct()
                ->paginate($num, ['*'], 'page', $page);
            $data=[];
            foreach($freelancers->items()as $freelancer){
                unset($freelancer->password);
                unset($freelancer->email_verified_at);
                $freelancer->skill=DB::table('skills')->select('skills.*')
                ->join('skill_freelancer_map', 'skills.id', '=', 'skill_freelancer_map.skill_id')->where('skill_freelancer_map.freelancer_id',"=",$freelancer->id)->get();
                $freelancer->major=DB::table('majors')->select('majors.*')
                ->join('major_freelancer_map', 'majors.id', '=', 'major_freelancer_map.major_id')->where('major_freelancer_map.freelancer_id',"=",$freelancer->id)->get();

                $data[] = $freelancer;
            }
            shuffle($data);
            return [
                'data' => $data,
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
