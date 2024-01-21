<?php

namespace App\Services;

interface IAdminService
{
    public function create($attributes = []);
    public function getList($num=10,$page=1,$searchKeyword='',$id);
    public function update($id,$position,$status);
    public function updateAtribute($id,$attribute);
    public function destroy($id);
}
