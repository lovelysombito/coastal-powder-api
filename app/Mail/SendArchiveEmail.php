<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendArchiveEmail extends Mailable
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
        if ($this->data['file']) {
            return $this->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
                ->subject("Coastal Powder Packing Slip")
                ->view('email-template.archive-email', [
                    'data' => $this->data,
                    'type' => "archive"
                ])->attach($this->data['file']);
        }
        return $this->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
            ->subject("Coastal Powder Packing Slip")
            ->view('email-template.archive-email', [
                'data' => $this->data,
                'type' => "archive"
            ]);
    }
}
