<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\CommentRead;
use Exception;
use App\Models\User;
use App\Models\LineItems;
use App\Models\JobScheduling;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
use App\Events\JobEvent;
use App\Models\ObjectNotification;
use App\Http\Resources\CommentResource;
use App\Jobs\HubSpot\AddDealNoteReply;
use App\Jobs\HubSpot\CreateDealNote;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function getComment(Request $request)
    {
        Log::info("CommentController@getComment", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {

            if (!isset($request->user_id)) {
                return response()->json([
                    'status' => 'Bad Request',
                    'code' => 404,
                    'message' => 'User id is missing.'
                ], 404);
            }

            $userId = $request->user_id;
            $comments = Comment::where('user_id', $userId)->get();

            if (count($comments) == 0) {
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => []
                ], 200);
            }

            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'message' => $comments
            ], 200);
        } catch (Exception $e) {
            Log::error("CommentController@getComment - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return response()->json([
                'status' => 'Bad Request',
                'code' => config('constant.status_code.not_found'),
                'message' => config('constant.error_message')
            ], config('constant.status_code.not_found'));
       }
    }

    public function readComment(Request $request, $commentId)
    { 
        Log::info("CommentController@readComment", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $userId = $request->user_id;

            $comment = CommentRead::where([
                ['comment_id', $commentId],
                ['user_id', $userId]
            ])->first();

            if ($comment) {
                throw new Exception('Comment cannot be updated.');
            }

            $newCommentRead = new CommentRead([
                'comment_id' => $commentId,
                'user_id' => $userId
            ]);

            $newCommentRead->save();

            return response()->json($newCommentRead);
        } catch (Exception $e) {
            Log::error("CommentController@readComment - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return response()->json([
                'status' => 'Bad Request',
                'code' => config('constant.status_code.not_found'),
                'message' => config('constant.error_message')
            ], config('constant.status_code.not_found'));
       }
    }

    public function updateComment(CommentRequest $request, $commentId)
    {
        Log::info("CommentController@updateComment", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            $comment = $request->comment;

            $selectedComment = Comment::find($commentId);
            if (!$comment) {
                return response()->json([
                    'status' => 'Bad Request',
                    'code' => 400,
                    'message' => 'Comment cannot be updated.'
                ], 400);
            }

            $selectedComment->comment = $comment;
            $selectedComment->save();

            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'data' => $selectedComment,
            ], 200);
        } catch (Exception $e) {
            Log::error("CommentController@updateComment - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return response()->json([
                'status' => 'Bad Request',
                'code' => config('constant.status_code.not_found'),
                'message' => config('constant.error_message')
            ], config('constant.status_code.not_found'));
       }
    }

    public function addComment(CommentRequest $request)
    {
       
        Log::info("CommentController@addComment", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {

            $userId = $request->user_id;
            $parentId = $request->parent_id;
            $objectId = $request->object_id;
            $objectType = $request->object_type;
            $comments = $request->comment;
            $mentioned_users = $request->mentioned_users;
            $notification_object_type = $request->notification_object_type;

            if ($userId) {
                $users = User::find($userId);
                if (!isset($users))
                    throw new Exception('No users available');
            }
            if ($objectId) {
                $lineItems = LineItems::where('line_item_id', $objectId)->first();
                $jobScheduling = JobScheduling::where('job_id', $objectId)->first();
            }
            if (empty($lineItems))
                if (empty($jobScheduling))
                    throw new Exception('No Line or Jobs available');

            if (!empty($parentId)) {
                $comment = Comment::where('comment_id', $parentId)->first();
                if (empty($comment))
                    throw new Exception('No comments available');
            }

            $addComment = new Comment([
                'user_id' => $userId,
                'parent_id' => $parentId,
                'object_id' => $objectId,
                'object_type' => $objectType,
                'comment' => $comments
            ]);

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $addComment->save();

            if(count($mentioned_users) > 0){

                for ($i=0; $i < count($mentioned_users); $i++) {

                    $notificationExist = ObjectNotification::where([
                        ["user_id", $mentioned_users[$i]['id']],
                        ["object_id", $addComment->comment_id]
                    ])->first();

                    if(!$notificationExist){
                        $addCommentNotification = new ObjectNotification([
                            'object_id' => $addComment->comment_id,
                            'user_id' => $mentioned_users[$i]['id'],
                            'viewed' => 'false',
                            'object_type' => $notification_object_type
                        ]); 
                    }
                    $addCommentNotification->save();
                    
                }
            }
            $event = [
                'userId' => $request->user_id,
                'parentId' => $request->parent_id,
                'objectId' => $request->object_id,
                'objectType' => $request->object_type,
                'comments' => $request->comment,
                'mentioned_users' => $request->mentioned_users,
                'notification_object_type' => $request->notification_object_type,
                'commentId' => $addComment->comment_id
            ];

            if (!$parentId) {
                CreateDealNote::dispatch($event);
            } else {
                $originalComment = Comment::find($parentId);
                if ($originalComment) {
                    $comments = Comment::where('parent_id', $parentId)->orderBy('created_at', 'asc')->get();
                    $newComment = '';
                    if ($comments) {
                        foreach ($comments as $key => $comment) {
                            $user = User::find($comment->user_id);
                            $newComment = "$newComment <br> <b>$user->lastname, $user->firstname :</b> $comment->comment";
                        }
                    }

                    $event['hsObjectId'] = $comment->hs_object_id;
                    $event['comments'] = "$originalComment->comment<br> $newComment";
                    AddDealNoteReply::dispatch($event);
                }
               
            }
            
            //Event call
            event(new JobEvent("job event call"));

            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'message' => 'Comment added OK.',
                'data' => new CommentResource($addComment)
            ], 200);
        } catch (Exception $e) {
            Log::error("CommentController@addComment - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return response()->json([
                'status' => 'Bad Request',
                'code' => config('constant.status_code.not_found'),
                'message' => config('constant.error_message')
            ], config('constant.status_code.not_found'));
       }
    }

    public function notification(Request $request)
    {
        Log::info("CommentController@notification", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            if (Auth::user()) {
                if (Auth::user()->notifications_new_comments == 'enabled') {
                    $userId = Auth::user()->user_id;
                    $commentList = Comment::with('users')->doesnthave('commentRead')->where('user_id', $userId)->paginate(config('constant.pagination.comment'));

                    if (Auth::user()->notifications_comment_replies == 'enabled') {
                        $commentList = Comment::with('users', 'repliedComment')->doesnthave('commentRead')->where('user_id', $userId)->paginate(config('constant.pagination.comment'));
                    }
                    if ($commentList->total() > 0) {
                        return ResponseHelper::responseMessage(config('constant.status_code.success'), $commentList, 'Comments');
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
    
    public function getAllComments(Request $request)
    {
        Log::info("CommentController@getAllComments", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {

            if($request->user()->scope === "administrator" || $request->user()->scope === "supervisor"){                
                $comments = Comment::with('job','users')->orderBy('created_at')->get();

            }  else {

                if (!isset($request->user()->user_id)) {
                    return response()->json([
                        'status' => 'Bad Request',
                        'code' => 404,
                        'message' => 'User id is missing.'
                    ], 404);
                }
    
                $userId = $request->user()->user_id;
                $comments = Comment::with('users', 'job')->where('user_id', $userId)->orderBy('created_at')->get();

            }
            
            if (count($comments) == 0) {
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => []
                ], 200);
            }

            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'message' => $comments
            ], 200);

        } catch (Exception $e) {
            Log::error("CommentController@getAllComments - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return response()->json([
                'status' => 'Bad Request',
                'code' => config('constant.status_code.not_found'),
                'message' => config('constant.error_message')
            ], config('constant.status_code.not_found'));
       }
    }

}