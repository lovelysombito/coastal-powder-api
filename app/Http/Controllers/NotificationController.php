<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\ObjectNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function commentNotifications(Request $request)
    {
        Log::info("NotificationController@commentNotification", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            if (Auth::user()) {
                if (Auth::user()->notifications_new_comments == 'enabled') {
                    $userId = Auth::user()->user_id;

                    $notificationList = ObjectNotification::with('user', 'comment')->where('user_id', $userId)->paginate(config('constant.pagination.comment'));

                    // if (Auth::user()->notifications_comment_replies == 'enabled') {
                    //     $notificationList = ObjectNotification::with('user', 'comment')->where('user_id', $userId)->paginate(config('constant.pagination.comment'));
                    // }
                    if ($notificationList->total() > 0) {
                        return ResponseHelper::responseMessage(config('constant.status_code.success'), $notificationList, 'Comments');
                    } else {
                        return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Comments');
                    }
                } else {
                    return ResponseHelper::errorResponse('Please enabled notifications new comments', config('constant.status_code.success'));
                }
            } else {
                return ResponseHelper::errorResponse('Please login first', config('constant.status_code.bad_request'));
            }
        } catch (Exception $e) {
            Log::error("CommentController@notification - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }

    }

    public function getCommentNotification($id)
    { 
        $notification = ObjectNotification::where("notification_id", $id)
                                            ->with('user', 'comment')->first();

        if($notification){

            $object_type = $notification->object_type;

            if($object_type === "COMMENT"){

                $comment = Comment::where("comment_id", $notification->object_id)->with("repliedComment", "users")->first();

                if($comment->parent_id){
                    Log::info("ParentID exists");
                    $comment  = Comment::where("comment_id", $comment->parent_id)->with("repliedComment", "users")->first();
                }

                return response()->json([
                    'status' => 'OK',
                    'code' => 200,
                    'data' => $comment,
                ], 200);

            }

           
        }

        return response()->json([
            'status' => 'Bad Request',
            'code' => 400,
            'data' => '',
        ], 400);
    }

    public function patchViewNotification($id)
    {
       $notification = ObjectNotification::where("notification_id", $id)->first();
        if($notification){
            $notification->viewed = 'true';
            if($notification->update()){
                return response()->json([
                    'status' => 'OK',
                    'code' => 200,
                ]);
            }
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'data' => $notification
            ]);
        }
    }
}
