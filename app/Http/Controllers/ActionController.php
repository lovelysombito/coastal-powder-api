<?php

namespace App\Http\Controllers;

use App\Mail\sendUserInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ActionController extends Controller
{
   public function verifyCode(Request $request) {

   }
}
