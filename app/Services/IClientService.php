<?php

namespace App\Services;

interface IClientService
{
    public function create($attributes = []);
    public function getList($num=10,$page=1,$searchKeyword='',$id,$status=null,$sex=null);
    public function update($id,$position,$status);
    public function updateAtribute($id,$attribute);
    public function destroy($id);
}
