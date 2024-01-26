<?php

namespace App\Services;

interface IJobService
{
    public function create($attributes = []);
    public function getList($num=10,$page=1,$searchKeyword='',$client_info,$min_proposal,$id,$status,$bids);
    public function updateAtribute($id,$attribute);
    public function destroy($id);
}
