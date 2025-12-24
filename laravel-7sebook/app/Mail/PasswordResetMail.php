<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetUrl;
    public $username;

    /**
     * Create a new message instance.
     */
    public function __construct($resetUrl, $username = null)
    {
        $this->resetUrl = $resetUrl;
        $this->username = $username;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('七秒电子书 - 密码重置')
            ->view('emails.password_reset')
            ->with([
                'resetUrl' => $this->resetUrl,
                'username' => $this->username,
            ]);
    }
}
