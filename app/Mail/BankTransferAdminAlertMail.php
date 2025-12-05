<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BankTransferAdminAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $donation;

    public function __construct(Donation $donation)
    {
        $this->donation = $donation;
    }

    public function build()
    {
        return $this->subject('Pending Bank Transfer Donation - ' . $this->donation->reference)
                    ->view('emails.bank-transfer-admin-alert');
                    //emails.bank-transfer-admin-alert
    }
}
