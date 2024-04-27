<?php

namespace App\Services;

interface IFreelancerService
{
    public function create($attributes = []);
    public function getList($num=10,$page=1,$searchKeyword='',$id,$status=null,$sex=null);
    public function updateAtribute($id,$attribute);
    public function destroy($id);
    public function getById($id);
    public function autoGetFreelancer($page,$num);
    public function searchListFreelancer($page,$num,$keyword, $skills,$majors, $date_of_birth, $expected_salary, $sex);
}
