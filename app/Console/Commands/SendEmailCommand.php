<?php

namespace App\Console\Commands;

use App\Mail\OverdueEmail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:send_email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email to all administrator user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (env('SEND_EMAIL_OVERDUE', 'true')) {
            $users = User::where('scope', 'administrator')->get();
            foreach ($users as $key => $user) {
                $data = [
                    'name' => $user->lastname.', '.$user->firstname, 
                    'url' => env('UX_URL'),
                    'api_url' => env('APP_URL')
                ];
                Mail::to(env('ADMIN_MAIL'))->send(new OverdueEmail($data));
            }
        }

        return 0;
    }
}
