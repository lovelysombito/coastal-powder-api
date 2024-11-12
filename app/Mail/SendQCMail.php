<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendQCMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if($this->data['image']){
            return $this->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
                ->subject($this->data['subject'])
                ->view('email-template.qc-failer', [
                    'data' => $this->data,
                    'type' => "qc"
                ])->attach($this->data['image']);
        }
        return $this->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
                ->subject($this->data['subject'])
                ->view('email-template.qc-failer', [
                    'data' => $this->data,
                    'type' => "qc"
                ]);
    }
}
