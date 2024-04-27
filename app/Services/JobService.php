<?php

namespace App\Services;

use App\Models\Job;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class JobService implements IJobService
{
    public function create($attributes = [])
    {
        try {
            $skill = $attributes['skill'];
            unset($attributes['skill']);
            $data = Job::create($attributes);
            //xử lý thêm skill
            if ($skill && count($skill) > 0) {
                foreach ($skill as $i) {
                    $tmp = DB::table('skill_job_map')->insert([
                        'job_id' => $data->id,
                        'skill_id' => $i
                    ]);
                }
            }

            $data['skills'] = DB::table('skill_job_map')
                ->join('skills', 'skill_job_map.skill_id', '=', 'skills.id')
                ->where('skill_job_map.job_id', '=', $data->id)
                ->select('skills.id as skill_id', 'skills.desc as skill_desc', 'skills.name as skill_name')
                ->get();;
            return  $data;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }
    public function updateWithData($id, $attributes = [])
    {
        try {
            $skill = $attributes['skill'];
            unset($attributes['skill']);
            $data = Job::findOrFail($id);

            // Cập nhật bản ghi Job với các thuộc tính mới
            $data->update($attributes);
            //xử lý thêm skill
            if ($skill && count($skill) > 0) {
                DB::table('skill_job_map')->where('job_id', $data->id)->delete();
                foreach ($skill as $i) {
                    $tmp = DB::table('skill_job_map')->insert([
                        'job_id' => $data->id,
                        'skill_id' => $i
                    ]);
                }
            }

            $data['skills'] = DB::table('skill_job_map')
                ->join('skills', 'skill_job_map.skill_id', '=', 'skills.id')
                ->where('skill_job_map.job_id', '=', $data->id)
                ->select('skills.id as skill_id', 'skills.desc as skill_desc', 'skills.name as skill_name')
                ->get();;
            return  $data;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function getList($num = 10, $page = 1, $searchKeyword = '', $client_info, $id, $bids, $status)
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
            $query->orderBy('updated_at', 'desc');

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

    public function getById($id)
    {
        $data = Job::find($id);
        if($data==null)return [];
        $data['skills'] = DB::table('skill_job_map')
            ->join('skills', 'skill_job_map.skill_id', '=', 'skills.id')
            ->where('skill_job_map.job_id', '=', $data->id)
            ->select('skills.id as skill_id', 'skills.desc as skill_desc', 'skills.name as skill_name')
            ->get();
        return  $data;
    }

    //     public function getListJobForFreelancer($page=1,$perPage=10)
    // {
    //     global $user_info;
    //     $freelancerId = $user_info->id;

    //     // Lấy danh sách các kỹ năng và điểm của freelancer
    //     $skills = DB::table('skill_freelancer_map')
    //         ->where('freelancer_id', $freelancerId)
    //         ->pluck('skill_id', 'skill_points')
    //         ->toArray();

    //     // Chuyển collection sang mảng
    //     $skillIds = array_keys($skills);

    //     // Khởi tạo mảng để lưu các công việc đã được thêm vào danh sách
    //     $addedJobs = [];

    //     // Khởi tạo mảng để lưu các kỹ năng của các công việc
    //     $allSkills = [];


    //     $addedJobs = [];
    //     $orderByExpression = implode(',', $skillIds);
    // $jobs = DB::table('jobs')
    //     ->select('jobs.*')
    //     ->join('skill_job_map', 'jobs.id', '=', 'skill_job_map.job_id')
    //     ->orderByRaw("FIELD(skill_job_map.skill_id, $orderByExpression) DESC")
    //     ->orderBy('skill_job_map.skill_points', 'DESC')
    //     ->orderByDesc('jobs.created_at')
    //     ->get();

    //     $filteredJobs = collect([]);
    //     foreach ($jobs as $job) {
    //         ///lúc này mới so sánh cùng skill_id thì xếp tiếp skill points
    //         if (!in_array($job->id, $addedJobs)) {
    //             $filteredJobs->push($job);
    //             $addedJobs[] = $job->id;
    //         }
    //     }

    //     //$paginatedJobs = $filteredJobs->paginate(10);

    //     // Tạo một collection
    // $collection = collect($filteredJobs);


    // // Số trang hiện tại
    // //$page = LengthAwarePaginator::resolveCurrentPage();

    // // Tạo một slice của collection để hiển thị trên trang hiện tại
    // $currentPageItems = $collection->slice(($page - 1) * $perPage, $perPage)->all();

    // // Tạo đối tượng LengthAwarePaginator
    // $paginatedJobs= new LengthAwarePaginator($currentPageItems, $collection->count(), $perPage, $page);
    //     dd($paginatedJobs->items());
    //     // return [
    //     //     'data' => $data,
    //     //     'total' => $total,
    //     //     'total_page' => $totalPages,
    //     //     'num' => $num,
    //     //     'current_page' => $page,
    //     // ];
    // }
    public function getListJobForFreelancer($page = 1, $perPage = 10)
    { 
        $page = $page ? $page : 1;
        $perPage = $perPage ? $perPage : 10;
        global $user_info;
        $freelancerId = $user_info->id;

        // Lấy danh sách các kỹ năng và điểm của freelancer
        $skills = DB::table('skill_freelancer_map')
            ->where('freelancer_id', $freelancerId)
            ->pluck('skill_id')
            ->toArray();

        // Chuyển collection sang mảng
        $skillIds = array_keys($skills);

        // Khởi tạo mảng để lưu các công việc đã được thêm vào danh sách
        $addedJobs = [];

        // Khởi tạo mảng để lưu các kỹ năng của các công việc
        $allSkills = [];

        $orderByExpression = implode(',', $skillIds);
        if(count($skillIds)==0)
            $orderByExpression=0;
        $jobs = DB::table('jobs')
            ->select('jobs.*')
            ->join('skill_job_map', 'jobs.id', '=', 'skill_job_map.job_id')
            ->orderByRaw("FIELD(skill_job_map.skill_id, ".$orderByExpression.") DESC")
            //->orderBy('skill_job_map.skill_points', 'DESC')
            ->orderByDesc('jobs.created_at')
            ->get();
        $filteredJobs = collect([]);
        foreach ($jobs as $job) {
            // Lúc này mới so sánh cùng skill_id thì xếp tiếp skill points
            if (!in_array($job->id, $addedJobs)) {
                $filteredJobs->push($job);
                $addedJobs[] = $job->id;
            }
        }

        // Tạo đối tượng LengthAwarePaginator
        $paginatedJobs = new LengthAwarePaginator(
            $filteredJobs->forPage($page, $perPage), // Lấy trang hiện tại và số lượng trang trên mỗi trang
            $filteredJobs->count(), // Tổng số mục
            $perPage, // Số lượng mục trên mỗi trang
            $page, // Trang hiện tại
            ['path' => LengthAwarePaginator::resolveCurrentPath()] // Link đến trang
        );
        $data=$paginatedJobs->items();
        foreach ($data as &$item) {
            $infoSkill=DB::select("select b.* from skill_job_map a,skills b where a.skill_id=b.id and a.job_id=".$item->id);
            $item->Skills=$infoSkill;
        }
        return [
            'data' =>array_values($data),
            'total' => $paginatedJobs->total(),
            'total_page' => $paginatedJobs->lastPage(),
            'num' => $paginatedJobs->perPage(),
            'current_page' => $paginatedJobs->currentPage(),
        ];
        //dd($paginatedJobs->items()); // Trả về mảng các mục trên trang hiện tại
    }
    // public function getListJobFillterForFreelancer($page = 1, $perPage = 10, $skillList = null, $keyword = null, $bids = null, $status = null, $proposal = null, $deadline = null)
    // {
    //     $query = Job::query();

    //     // Áp dụng các bộ lọc nếu được truyền vào
    //     if ($keyword) {
    //         $query->where(function ($q) use ($keyword) {
    //             $q->where('title', 'like', "%$keyword%")
    //                 ->orWhere('desc', 'like', "%$keyword%")
    //                 ->orWhere('content', 'like', "%$keyword%");
    //         });
    //     }

    //     if ($bids) {
    //         $bidsRange = explode(',', $bids);
    //         if (count($bidsRange) === 2) {
    //             $query->whereBetween('bids', [$bidsRange[0], $bidsRange[1]]);
    //         }
    //     }

    //     if ($status) {
    //         $query->where('status', $status);
    //     }

    //     if ($proposal) {
    //         $proposalRange = explode(',', $proposal);
    //         if (count($proposalRange) === 2) {
    //             $query->whereBetween('min_proposals', [$proposalRange[0], $proposalRange[1]]);
    //         }
    //     }
    //     // Xử lý trường skillList nếu có
    //     if ($skillList) {
    //         $skills = explode(',', $skillList);
    //         $jobIds = DB::table('skill_job_map')->whereIn('skill_id', $skills)->pluck('job_id')->toArray();
    //         $query->whereIn('id', $jobIds);
    //     }

    //     if ($deadline) {
    //         $deadlineRange = explode(',', $deadline);
    //         if (count($deadlineRange) === 2) {
    //             $query->whereBetween('deadline', [$deadlineRange[0], $deadlineRange[1]]);
    //         }
    //     }

    //     // Lấy tổng số lượng records
    //     $total = $query->count();

    //     // Phân trang
    //     $data = $query->offset(($page - 1) * $perPage)
    //         ->limit($perPage)
    //         ->get();

    //     $totalPage = ceil($total / $perPage);

    //     return [
    //         'data' => $data,
    //         'total' => $total,
    //         'total_page' => $totalPage,
    //         'num' => $perPage,
    //         'current_page' => $page,
    //     ];
    // }
    public function getListJobFillterForFreelancer($page = 1, $perPage = 10, $skillList = null, $keyword = null, $bids = null, $status = null, $deadline = null)
    {
        $page = $page ? $page : 1;
        $perPage = $perPage ? $perPage : 10;
        $query = Job::query();
        // Áp dụng các bộ lọc nếu được truyền vào
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%$keyword%")
                    ->orWhere('desc', 'like', "%$keyword%")
                    ->orWhere('content', 'like', "%$keyword%");
            });
        }

        if (isset($bids)) {
            $bidsRange = explode(',', $bids);
            if (count($bidsRange) === 2) {
                $query->whereBetween('bids', [$bidsRange[0], $bidsRange[1]]);
            }
        }

        if (isset($status)) {
            $query->where('status', $status);
        }

        if ($skillList) {
            $skills = explode(',', $skillList);
            $jobIds = DB::table('skill_job_map')->whereIn('skill_id', $skills)->pluck('job_id')->toArray();
            $query->whereIn('id', $jobIds);
        }

        if ($deadline) {
            $deadlineRange = explode(',', $deadline);
            if (count($deadlineRange) === 2) {
                $query->whereBetween('deadline', [$deadlineRange[0], $deadlineRange[1]]);
            }
        }
        // Lấy tổng số lượng records
        $totalQuery = clone $query;
        $total = $totalQuery->count();
    
        $data = $query->limit($perPage);

        if ($page > 1) {
            $data = $data->offset(($page - 1) * $perPage);
        }
        
        $data = $data->get()->toArray();
        foreach ($data as &$item) {
            $infoSkill=DB::select("select b.* from skill_job_map a,skills b where a.skill_id=b.id and a.job_id=".$item['id']);
            $item['Skills']=$infoSkill;
        }
        $totalPage = ceil($total / $perPage);

        return [
            'data' => (array)$data,
            'total' => $total,
            'total_page' => $totalPage,
            'num' => $perPage,
            'current_page' => $page,
        ];
    }
}
