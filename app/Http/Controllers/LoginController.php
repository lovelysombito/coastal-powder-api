<?php

namespace App\Http\Controllers;

use App\Mail\SendCodeMail;
use App\Models\User;
use App\Models\UserCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function login(Request $request) {

        $validator = Validator::make($request->all(), [
            'email' => 'string|required|email',
            'password' => 'string|required',
            'remember_me' => 'boolean',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['message' => 'The provided credentials are invalid'], 200);
        }
        
        $email = $request->email;
        $password = $request->password;

        if ($password == '' || $password == null || !Auth::attemptWhen(['email' => $email, 'password' => $password, 'deleted_at' => null, 'confirmation_token' => null], function ($user) {
            return $user->email_verified_at;
        }, $request->remember_me)) {
            return response()->json(['message' => 'The provided credentials are invalid'], 200  );
        }

        $user = Auth::user();

        $request->session()->regenerate();

        return response()->json(['message' => 'Success', "data" => $user], 200);
    }

    public function logout(Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Success'], 200);
    }

    /**
     * Method defined to return a token, not create a session. 
     * The sessions will be easier to implement for the UI and token management is not required
     */
    // public function loginToken(Request $request) {

    //     $request->validate([
    //         'email' => 'string|required|email',
    //         'password' => 'string|required',
    //     ]);
        
    //     $email = $request->email;
    //     $password = $request->password;

    //     if ($password == '' || $password == null || !Auth::attempt(['email' => $email, 'password' => $password, 'deleted_at' => null, 'confirmation_token' => null])) {
    //         return response()->json(['message' => 'The provided credentials are invalid'], 401);
    //     }

    //     $user = Auth::user();

    //     $token = $request->user()->createToken("token", [$user->scope])->plainTextToken;

    //     return response()->json(['message' => 'Success', 'token'=>$token], 200);
    // }
}
