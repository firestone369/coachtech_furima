<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KonbiniPaymentInstructionsMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $details;

    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function build()
    {
        return $this->subject('【お支払い手続き】コンビニ決済のご案内')
            ->view('emails.konbini_instructions');
    }
}
