<?php

namespace App\Services;

interface IJobService
{
    public function create($attributes = []);
    public function getList($num=10,$page=1,$searchKeyword='',$client_info,$id,$status,$bids);
    public function updateAtribute($id,$attribute);
    public function destroy($id);
    public function getJobByAtribute(array $attributes, array $values,$page,$num);
    public function getById($id);
    public function updateWithData($id, $attributes = []);
    public function getListJobForFreelancer($page=1,$perPage=10);
    public function getListJobFillterForFreelancer($page=1,$perPage=10,$skillList = null,$keyword=null,$bids=null,$status=null,$proposal=null,$deadline=null);
}
