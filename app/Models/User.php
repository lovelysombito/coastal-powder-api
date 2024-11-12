<?php

namespace App\Models;

use App\Exports\ProductImportFailure as ProductImportFailureExport;
use App\Notifications\ProductImportFailure;
use App\Notifications\ProductImportSuccess;
use App\Notifications\VerifyEmail;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'scope',
        'password',
        'notifications_new_comments',
        'notifications_comment_replies',
        'notifications_tagged_comments',
        'confirmation_token',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
        'deleted_at',
        'confirmation_token',
        'two_factor_confirmed_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'email_verified_at',
        'notifications_new_comments',
        'notifications_comment_replies',
        'notifications_tagged_comments',
        'remember_token',
    ];

    public function sendEmailVerificationNotification() {

        $payload = [
            'iss' => env('APP_URL'),
            'aud' => env('APP_URL'),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time()+1800,
            'user_id' => $this->user_id,
        ];

        do {
            $token = JWT::encode($payload, env('APP_KEY'), 'HS256');
        } while (!User::where('confirmation_token', $token));

        $this::update([
            'confirmation_token' => $token,
        ]);

        $this->notify(new VerifyEmail($token));
    }

    public function sendProductImportSuccess() {
        $this->notify(new ProductImportSuccess());
    }

    public function sendProductImportFailure($failures,$fromProductController=false) {

        $data = [['Row', 'Error', 'Initial Values']];
        if($fromProductController){
            $dataRow = [
                'row' => 1,
                'errors' => $failures,
            ];
            array_push($data, $dataRow);
        }else {
        foreach($failures as $failure) {
            $dataRow = [
                'row' => $failure->row(),
                'errors' => $failure->errors()[0],
            ]; 
    
            $failureValues = $failure->values();
            array_walk($failureValues, function(&$value, $key) { $value = $key . " - " . $value; });
            $dataRow = array_merge($dataRow, $failureValues);
            array_push($data, $dataRow);
        }
        }
        
        $fileName = Uuid::uuid4()->toString().'-errors.csv';
        $export = Excel::store(new ProductImportFailureExport($data), $fileName, 'local', \Maatwebsite\Excel\Excel::CSV);

        $url = Storage::disk('local')->path($fileName);

        $this->notify(new ProductImportFailure($url));

        Storage::disk('local')->delete($fileName);
    }
}
