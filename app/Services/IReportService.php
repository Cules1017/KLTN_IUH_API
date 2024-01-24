<?php

namespace App\Services;

interface IReportService
{
    public function create($attributes = []);
    public function getList($num=10,$page=1,$type_result,$id);
    public function updateAtribute($id,$attribute);
    public function destroy($id);
}
