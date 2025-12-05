<?php


namespace App\Services;

use App\Models\Donation;
use App\Models\DonationNotification;
use App\Mail\DonorReceiptMail;
use App\Mail\AdminDonationAlertMail;
use App\Mail\BankTransferThankYouMail;
use App\Mail\BankTransferAdminAlertMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send receipt to donor
     */
    public function sendDonorReceipt(Donation $donation)
    {
        try {
            $notification = DonationNotification::create([
                'donation_id' => $donation->id,
                'type' => 'donor_receipt',
                'recipient_email' => $donation->donor_email,
                'status' => 'pending'
            ]);

            Mail::to($donation->donor_email)
                ->send(new DonorReceiptMail($donation));

            $notification->markAsSent();

            Log::info('Donor receipt sent', [
                'donation_id' => $donation->id,
                'email' => $donation->donor_email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send donor receipt: ' . $e->getMessage());

            if (isset($notification)) {
                $notification->markAsFailed($e->getMessage());
            }

            return false;
        }
    }

    /**
     * Send alert to admin/NGO
     */
    public function sendAdminAlert(Donation $donation)
    {
        try {
            $adminEmail = config('mail.admin_email', env('ADMIN_EMAIL'));

            $notification = DonationNotification::create([
                'donation_id' => $donation->id,
                'type' => 'admin_alert',
                'recipient_email' => $adminEmail,
                'status' => 'pending'
            ]);

            Mail::to($adminEmail)
                ->send(new AdminDonationAlertMail($donation));

            $notification->markAsSent();

            Log::info('Admin alert sent', [
                'donation_id' => $donation->id,
                'email' => $adminEmail
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send admin alert: ' . $e->getMessage());

            if (isset($notification)) {
                $notification->markAsFailed($e->getMessage());
            }

            return false;
        }
    }

    /**
     * Retry failed notifications
     */
    public function retryFailedNotifications($maxRetries = 3)
    {
        $failedNotifications = DonationNotification::where('status', 'failed')
            ->where('retry_count', '<', $maxRetries)
            ->get();

        foreach ($failedNotifications as $notification) {
            $donation = $notification->donation;

            if ($notification->type === 'donor_receipt') {
                $this->sendDonorReceipt($donation);
            } elseif ($notification->type === 'admin_alert') {
                $this->sendAdminAlert($donation);
            }
        }
    }

    /**
     * Send thank you email for bank transfer donation
     */
    public function sendBankTransferThankYou(Donation $donation)
    {
        try {
            $notification = DonationNotification::create([
                'donation_id' => $donation->id,
                'type' => 'bank_transfer_thank_you',
                'recipient_email' => $donation->donor_email,
                'status' => 'pending'
            ]);

            Mail::to($donation->donor_email)
                ->send(new BankTransferThankYouMail($donation));

            $notification->markAsSent();

            Log::info('Bank transfer thank you sent', [
                'donation_id' => $donation->id,
                'email' => $donation->donor_email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send bank transfer thank you: ' . $e->getMessage());

            if (isset($notification)) {
                $notification->markAsFailed($e->getMessage());
            }

            return false;
        }
    }

    /**
     * Send admin alert for pending bank transfer
     */
    public function sendBankTransferAdminAlert(Donation $donation)
    {
        try {
            $adminEmail = config('mail.admin_email', env('ADMIN_EMAIL'));

            $notification = DonationNotification::create([
                'donation_id' => $donation->id,
                'type' => 'bank_transfer_admin_alert',
                'recipient_email' => $adminEmail,
                'status' => 'pending'
            ]);

            Mail::to($adminEmail)
                ->send(new BankTransferAdminAlertMail($donation));

            $notification->markAsSent();

            Log::info('Bank transfer admin alert sent', [
                'donation_id' => $donation->id,
                'email' => $adminEmail
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send bank transfer admin alert: ' . $e->getMessage());

            if (isset($notification)) {
                $notification->markAsFailed($e->getMessage());
            }

            return false;
        }
    }
}
