<?php

namespace App\Services;

use App\Models\Notifications;
use Exception;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class NotificationService implements INotificationService
{
    public function getMyNotifications(){
        global $user_info;
        $data = Notifications::where('user_id', $user_info->id)
        ->where('type_user', $user_info->user_type)
        ->where('time_push', '<=', now())
        ->orderBy('time_push')
        ->get();
        return $data;

    }
    public function createNoti($attributes = [],$sendMail=false)
    {
        try {
            global $user_info;
            if($sendMail){
                Mail::send('mailnoti', ['title' => $attributes['title'],'message_mail'=>$attributes['message']], function ($message) use ($user_info,$attributes) {
                    $message->to($user_info->email, $user_info->first_name)->subject($attributes['title']);
                });
            }
            return Notifications::create(array_merge(["user_id"=>$user_info->id,"noti_type"=>0,"type_user"=>$user_info->user_type,"is_read"=>0,"time_push"=>now()],$attributes) );
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }
    public function updateAtribute($id,$attribute){
        try {
            $admin=Notifications::findOrFail($id);
            $admin->update($attribute);
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }

    public function destroy($id){
        try {
            $admin=Notifications::findOrFail($id);
            $admin->destroy();
            return $admin;
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage(), null, 400);
        }
    }


}