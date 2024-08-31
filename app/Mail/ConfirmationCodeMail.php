<?php
// app/Mail/ConfirmationCodeMail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $confirmationCode;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($confirmationCode)
    {
        $this->confirmationCode = $confirmationCode;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Email Confirmation Code')
                    ->view('emails.confirmation_code'); // Create this view for email content
    }
}