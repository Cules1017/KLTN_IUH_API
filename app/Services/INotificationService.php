<?php

namespace App\Services;

interface INotificationService
{
    public function getMyNotifications();
    public function createNoti($attributes = [],$sendMail);
    public function updateAtribute($id,$attribute);
    public function destroy($id);
}
