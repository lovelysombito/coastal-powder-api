<?php

namespace App\Http\Controllers;

use App\Events\JobEvent;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function getAllUsers(Request $request) {
       try {
        $users = User::all();

        if (count($users) == 0)
            throw new Exception('No users available');

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => $users
        ]);
        
       } catch (Exception $e) {
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => config('constant.error_message')
            ], 400);
       }
    }

    public function getUser(Request $request)
    {
        $user = $request->user();

        try {
            
            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'data' => $user
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => config('constant.error_message')
            ], 400);
        }
    }

    public function getNotificationOption(Request $request)
    {
        try {

            $user = User::find($request->user()->user_id);
            
            if(!$user){ 
                Log::warning("UserController@getNotificationOption - No user found with id ". $user->user_id, ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
                return response()->json(['message'=>'No user found with id'], 401);
            }

            $data = [
                "user_id" => $user->user_id,
                "notifications_new_comments" => $user->notifications_new_comments,
                "notifications_comment_replies" => $user->notifications_comment_replies,
                "notifications_tagged_comments" => $user->notifications_tagged_comments
            ];

            Log::info("UserController@getNotificationOption - user found with notification details". $user->user_id, ["req"=>['ip' => $request->ip(), 'user'=>'verified']]);
            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'data' => $data
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => config('constant.error_message')
            ], 400);
        }

    }

    public function updateNotificationOption(Request $request, $userId)
    {
        try {

            $user = User::find($userId);
            if(!$user){ 
                Log::warning("UserController@updateNotificationOption - No user found with id ". $user->user_id, ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
                return response()->json(['message'=>'No user found with id'], 401);
            }

            $notifications_new_comments = $request->notifications_new_comments ? $request->notifications_new_comments : $user->notifications_new_comments;
            $notifications_comment_replies = $request->notifications_comment_replies ? $request->notifications_comment_replies : $user->notifications_comment_replies;
            $notifications_tagged_comments = $request->notifications_tagged_comments ? $request->notifications_tagged_comments : $user->notifications_tagged_comments;
            
            $user->notifications_new_comments = $notifications_new_comments;
            $user->notifications_comment_replies = $notifications_comment_replies;
            $user->notifications_tagged_comments = $notifications_tagged_comments;

            if($user->save()){

                Log::info("UserController@getNotificationOption - User notification details successfully updated ". $user->user_id, ["req"=>['ip' => $request->ip(), 'user'=>'verified']]);
                $data = [
                    "notifications_new_comments" => $user->notifications_new_comments,
                    "notifications_comment_replies" => $user->notifications_comment_replies,
                    "notifications_tagged_comments" => $user->notifications_tagged_comments
                ];
    
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'data' => $data
                ], 200);
            }

            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => 'User notification options update invalid'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => config('constant.error_message')
            ], 400);
        }

    }

    public function get2faAuth(Request $request)
    {

        try {

            $user = $request->user();
            $two_factor = $user->two_factor_confirmed_at;

            if(is_null($two_factor)){
                return response()->json([
                    'code' => 200,
                    'data' => [
                        "user_id" => $user->user_id,
                        "two_factor" => "disabled"
                    ]
                ], 200); 
            }

            return response()->json([
                'code' => 200,
                'data' => [
                    "user_id" => $user->user_id,
                    "two_factor" => "enabled"
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => config('constant.error_message')
            ], 400);
        }
        
    }

    public function update2faAuth(Request $request, $userId)
    {
        try {
            
            $user = User::find($userId);
            $data = [];

            if(!$user){ 
                Log::warning("UserController@update2faAuth - No user found with id ". $user->user_id, ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
                return response()->json(['message'=>'No user found with id'], 401);
            }

            if(!is_null($user->two_factor_confirmed_at)){
                $user->two_factor_confirmed_at = null;
                $data = [
                    "user_id" => $user->user_id,
                    "two_factor" => "disabled"
                ];
            } else {
                $user->two_factor_confirmed_at = $user->freshTimestamp();
                $data = [
                    "user_id" => $user->user_id,
                    "two_factor" => "enabled"
                ];
            }

            if($user->update()){
                return response()->json([
                    'code' => 200,
                    'data' => $data
                ], 200);
            }

            return response()->json([
                'status' => "Bad Request",
                'code' => 400,
                'message' => "2fa Auth invalid update"
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => config('constant.error_message')
            ], 400);
        }

    }

    public function getUserPassword(Request $request)
    {

        try {

            $user = $request->user();

            return response()->json([
                'code' => 200,
                'data' => [
                    "user_id" => $user->user_id,
                    "password" => $user->password
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => config('constant.error_message')
            ], 400);
        }

    }

    public function updateUserPassword(Request $request)
    {

        try {

            $user_id = $request->user()->user_id;
            $user = User::find($user_id);

            if(!$user){ 
                Log::warning("UserController@updateUserPassword - No user found with id ". $user_id, ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
                return response()->json(['message'=>'No user found with id'], 401);
            }
           
            $password_matched = Hash::check($request->oldPassword , $user->password);

            if(!$password_matched){
                Log::warning("UserController@updateUserPassword - Incorrect Password ". $user_id, ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
                return response()->json([
                    'status' => 'Bad Request',
                    'code' => 400,
                    'message' => "Incorrect Password"
                ], 400);
            }

            $user->forceFill(['password'=>Hash::make($request->password)])->save();

            Log::info("UserController@updateUserPassword - User password successfully verified", ["req"=>['ip' => $request->ip(), 'user'=>$user->user_id]]);
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => "User password updated"
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => config('constant.error_message')
            ], 400);
        }
    }
    
    public function addUser(Request $request) {
        Log::info("UserController@addUser - Invite a new user", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]);

        $request->validate([
            'email'=>'string|required|email',
            'scope' => "string|in:administrator,supervisor,user",
            'firstname' => 'string|required',
            'lastname' => 'string|required',
        ]);

        try {

            $firstName = $request->firstname;
            $lastName = $request->lastname;
            $email = $request->email;
            $accessLevel = $request->scope;

            if (User::where('email', $email)->first()) {
                Log::warning("UserController@addUser - ".$email." already exists", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]);
                return response()->json(['message'=>'A user with this email address already exists'], 400);
            }

            $user = User::create([
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email' => $email,
                'scope' => $accessLevel,
            ]);

            $user->sendEmailVerificationNotification();

            event(new JobEvent('user'));
            Log::info("UserController@addUser - ".$email." has successfully been invited", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]);
            return response()->json(['message' => 'User has successfully been invited'], 200);

        } catch (Exception $e) {
            Log::error("UserController@addUser - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function verify(Request $request)
    {
        Log::info("UserController@verify - Verify Token", ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
        $request->validate([
            'token'=>'string|required',
            'password' => 'string|required|confirmed',
        ]);

        $token = $request->token;
        $password = $request->password;

        if (!$token) {
            Log::warning("UserController@verify - Invalid signature provided", ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
            return response()->json(['message'=>'Please provide a valid verification token'], 401);
        }

        try {
            $payload = JWT::decode($token, new Key(env('APP_KEY'), 'HS256'));
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            Log::warning("UserController@verify - Invalid signature provided", ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
            return response()->json(['message'=>'Please provide a valid verification token'], 401);
        } catch (\Firebase\JWT\ExpiredException $e) {
            Log::warning("UserController@verify - Expired verification token provided", ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
            return response()->json(['message'=>'Please provide a valid verification token'], 401);
        } catch (\UnexpectedValueException $e) {
            Log::warning("UserController@verify - ".$e->getMessage(), ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
            return response()->json(['message'=>'Please provide a valid verification token'], 401);
        } catch (Exception $e) {
            Log::error("UserController@verify - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'unverified'],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }

        if ($payload->aud !== env('APP_URL')) {
            Log::warning("UserController@verify - Invalid audience", ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
            return response()->json(['message'=>'Please provide a valid verification token'], 401);
        }

        $user = User::find($payload->user_id);
        if (!$user || $user->confirmation_token !== $token) {
            Log::warning("UserController@verify - No user found with id ".$payload->user_id, ["req"=>['ip' => $request->ip(), 'user'=>'unverified']]);
            return response()->json(['message'=>'Please provide a valid verification token'], 401);
        }

        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null, 'password'=>Hash::make($password)])->save();

        Log::info("UserController@verify - User successfully verified", ["req"=>['ip' => $request->ip(), 'user'=>$user->user_id]]);

        return response()->json(['message'=>'Email has successfully been verified'], 200);
        
    }

    public function resendVerificationEmail(Request $request, String $userId) {

        Log::info("UserController@resendVerificationEmail - Request email verification to be resent", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]);

        $validator = Validator::make(["userId"=>$userId], ['userId' => 'required|string']);
 
        if ($validator->fails()) {
            Log::warning("UserController@resendVerificationEmail - The user id has not been supplied in the URL parameter", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]);
            return response()->json(['message'=>'The requested user does not exist'], 404);
        }

        try {
            $user = User::find($userId);
            if (!$user) {
                Log::warning("UserController@resendVerificationEmail - The user id has not been found in the database", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]);
                return response()->json(['message'=>'The requested user does not exist'], 404);
            }

            if ($user->email_verified_at) {
                Log::warning("UserController@resendVerificationEmail - The user ". $userId ." has already verified their email address", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]);
                return response()->json(['message'=>'The user has already verified their email address'], 400);
            }

            $user->sendEmailVerificationNotification();
            Log::info("UserController@resendVerificationEmail - Verification email has been resent to ".$user->email, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]);

            return response()->json(['message'=>'The verification email has successfully been resent'], 200);
        } catch (Exception $e) {
            Log::error("UserController@resendVerificationEmail - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function deleteUser(Request $request, $user_id) {
        try {
            $user = User::find($user_id);

            if (!$user) {
                    return response()->json([
                        'status' => 'Bad Request',
                        'code' => 404,
                        'message' => "{$user_id} doesn't exist"
                    ]);
            }

            $user->delete();
            event(new JobEvent('user'));
            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'message' => 'Successfully deleted'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => config('constant.error_message')
            ], 400);
        }
    }

    public function updateUser(Request $request, $userId) {
        try {

            $firstName = $request->firstname;
            $lastName = $request->lastname;
            $email = $request->email;
            $accessLevel = $request->scope;
            
            $user = User::find($userId);
            if (!$user) 
                throw new Exception("User edit invalid.");

            if (!is_null($firstName)) 
                $user->firstname = $firstName;
            if (!is_null($lastName)) 
                $user->lastname = $lastName;
            if (!is_null($email)) 
                $user->email = $email;
            if (!is_null($accessLevel)) 
                $user->scope = $accessLevel;

            $user->save();
            event(new JobEvent('user'));
            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'message' => $user
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Bad Request',
                'code' => 400,
                'message' => config('constant.error_message')
            ], 400);
        }
    }

    // TODO https://github.com/jacktaylorgroup/coastal-powder-coating-api/issues/80
}
