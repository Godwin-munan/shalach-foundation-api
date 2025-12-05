<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Services\PaystackService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DonationController extends Controller
{
    public function __construct(
        private PaystackService $paystackService,
        private NotificationService $notificationService
    ) {}


/**
 * Handle bank transfer donation submission
 * Creates pending donation record and sends thank you email
 *
 * POST /api/donations/bank-transfer
 */
public function bankTransfer(Request $request)
{
    $validator = Validator::make($request->all(), [
        'donor_name' => 'required|string|max:255',
        'donor_email' => 'required|email|max:255',
        'donor_phone' => 'nullable|string|max:20',
        'amount' => 'required|numeric|min:100|max:10000000',
        'currency' => 'nullable|string|in:NGN,USD,GHS,ZAR,KES',
        'purpose' => 'nullable|string|max:500',
        'is_anonymous' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    DB::beginTransaction();

    try {
        // Generate unique reference for bank transfer
        $reference = 'BANK_' . strtoupper(uniqid()) . '_' . time();

        // Create pending donation record (status: pending_confirmation)
        $donation = Donation::create([
            'reference' => $reference,
            'donor_name' => $request->donor_name,
            'donor_email' => strtolower(trim($request->donor_email)),
            'donor_phone' => $request->donor_phone,
            'amount' => $request->amount,
            'currency' => $request->currency ?? 'NGN',
            'purpose' => $request->purpose,
            'is_anonymous' => $request->is_anonymous ?? false,
            'status' => 'pending_confirmation', // Different status for bank transfers
            'payment_channel' => 'bank_transfer',
            'ip_address' => $request->ip()
        ]);

        DB::commit();

        Log::info('Bank transfer donation submitted', [
            'reference' => $donation->reference,
            'amount' => $donation->amount,
            'email' => $donation->donor_email
        ]);

        // Send thank you email to donor
        $this->notificationService->sendBankTransferThankYou($donation);

        // Send admin notification about pending bank transfer
        $this->notificationService->sendBankTransferAdminAlert($donation);

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Thank you for your donation! Please transfer to the account details provided. We will send a confirmation message once your payment has been received and verified.',
            'data' => [
                'reference' => $donation->reference,
                'amount' => $donation->amount,
                'currency' => $donation->currency,
            ]
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Bank transfer donation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to process donation. Please try again.'
        ], 500);
    }
}

    /**
     * Initialize donation and get Paystack payment URL
     * Frontend calls this, receives authorization_url, redirects user to Paystack
     *
     * POST /api/donations/initialize
     */
    public function initialize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'donor_name' => 'required|string|max:255',
            'donor_email' => 'required|email|max:255',
            'donor_phone' => 'nullable|string|max:20',
            'amount' => 'required|numeric|min:100|max:10000000',
            'currency' => 'nullable|string|in:NGN,USD,GHS,ZAR,KES',
            'purpose' => 'nullable|string|max:500',
            'is_anonymous' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Generate unique reference before Paystack call
            $reference = 'DON_' . strtoupper(uniqid()) . '_' . time();

            // Create pending donation record
            $donation = Donation::create([
                'reference' => $reference,
                'donor_name' => $request->donor_name,
                'donor_email' => strtolower(trim($request->donor_email)),
                'donor_phone' => $request->donor_phone,
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'NGN',
                'purpose' => $request->purpose,
                'is_anonymous' => $request->is_anonymous ?? false,
                'status' => 'pending',
                'ip_address' => $request->ip()
            ]);

            // Initialize Paystack transaction
            $paystackData = $this->paystackService->initializeTransaction([
                'reference' => $donation->reference,
                'donor_email' => $donation->donor_email,
                'amount' => $donation->amount,
                'currency' => $donation->currency,
                'donor_name' => $donation->donor_name,
                'donor_phone' => $donation->donor_phone,
                'purpose' => $donation->purpose,
                'is_anonymous' => $donation->is_anonymous,
            ]);

            // Update donation with Paystack access code
            $donation->update([
                'paystack_access_code' => $paystackData['access_code']
            ]);

            DB::commit();

            Log::info('Donation initialized', [
                'reference' => $donation->reference,
                'amount' => $donation->amount,
                'email' => $donation->donor_email
            ]);

            // Return authorization URL to frontend
            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'reference' => $donation->reference,
                    'authorization_url' => $paystackData['authorization_url'],
                    'access_code' => $paystackData['access_code'],
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Donation initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment. Please try again.'
            ], 500);
        }
    }

    /**
     * Webhook endpoint - Paystack calls this after payment
     * This is where the REAL work happens
     *
     * POST /api/donations/webhook
     */
    public function webhook(Request $request)
    {
        // CRITICAL: Verify webhook signature
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (!$this->paystackService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        Log::info('Webhook received', [
            'event' => $event,
            'reference' => $data['reference'] ?? 'unknown'
        ]);

        try {
            // Handle successful charge
            if ($event === 'charge.success') {
                $this->handleSuccessfulPayment($data);
            }

            // Handle failed charge
            if ($event === 'charge.failed') {
                $this->handleFailedPayment($data);
            }

            return response()->json(['message' => 'Webhook processed'], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'event' => $event,
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            // Return 200 to prevent Paystack retry storm
            return response()->json(['message' => 'Webhook received'], 200);
        }
    }

    /**
     * (Optional): Frontend can check payment status
     * Called after user is redirected back from Paystack
     *
     * GET /api/donations/status/{reference}
     */
    public function checkStatus(string $reference)
    {
        try {
            $donation = Donation::where('reference', $reference)->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'reference' => $donation->reference,
                    'status' => $donation->status,
                    'amount' => $donation->amount,
                    'currency' => $donation->currency,
                    'paid_at' => $donation->paid_at?->toIso8601String(),
                    'payment_channel' => $donation->payment_channel
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Donation not found'
            ], 404);
        }
    }

    /**
     * Handle successful payment from webhook
     */
    private function handleSuccessfulPayment(array $data): void
    {
        $reference = $data['reference'];

        $donation = Donation::where('reference', $reference)->first();

        if (!$donation) {
            Log::warning('Donation not found for successful payment', ['reference' => $reference]);
            return;
        }

        // Prevent duplicate processing
        if ($donation->status === 'success') {
            Log::info('Payment already processed', ['reference' => $reference]);
            return;
        }

        DB::beginTransaction();

        try {
            // Verify with Paystack (double-check)
            $verificationData = $this->paystackService->verifyTransaction($reference);

            if (!$this->paystackService->isSuccessful($verificationData)) {
                Log::warning('Verification failed for webhook success event', [
                    'reference' => $reference,
                    'status' => $verificationData['status']
                ]);
                return;
            }

            // Update donation record
            $donation->update([
                'status' => 'success',
                'paystack_reference' => $data['reference'],
                'payment_channel' => $verificationData['channel'],
                'paid_at' => now(),
            ]);

            DB::commit();

            Log::info('Payment processed successfully', [
                'reference' => $reference,
                'amount' => $donation->amount,
                'channel' => $verificationData['channel']
            ]);

            // Send notifications asynchronously (won't block webhook response)
            $this->sendNotifications($donation);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to process successful payment', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle failed payment from webhook
     */
    private function handleFailedPayment(array $data): void
    {
        $reference = $data['reference'];

        $donation = Donation::where('reference', $reference)->first();

        if (!$donation) {
            return;
        }

        $donation->update([
            'status' => 'failed',
            'metadata' => $data
        ]);

        Log::info('Payment failed', [
            'reference' => $reference,
            'message' => $data['gateway_response'] ?? 'Unknown error'
        ]);
    }

    /**
     * Send email notifications (donor receipt + admin alert)
     */
    private function sendNotifications(Donation $donation): void
    {
        try {
            // Send donor receipt
            $this->notificationService->sendDonorReceipt($donation);

            // Send admin alert
            $this->notificationService->sendAdminAlert($donation);

        } catch (\Exception $e) {
            // Don't fail webhook if email fails
            Log::error('Notification sending failed', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getReferenceCode(): string {
        return 'DON_' . strtoupper(uniqid()) . '_' . time();
    }
}
