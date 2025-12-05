<?php


namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminDonationAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $donation;

    public function __construct(Donation $donation)
    {
        $this->donation = $donation;
    }

    public function build()
    {
        return $this->subject('New Donation Received - ' . $this->donation->currency . ' ' . number_format($this->donation->amount, 2))
                    ->view('emails.admin-alert');
    }
}
