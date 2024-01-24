<?php

namespace App\Services;

interface ISystermConfigService
{
    public function create($attributes = []);
    public function getList($num=10,$page=1,$searchKeyword='',$id);
    public function updateAtribute($id,$attribute);
    public function destroy($id);
}
