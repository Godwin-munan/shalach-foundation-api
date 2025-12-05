<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Iamolayemi\Paystack\Facades\Paystack;

class PaystackService
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret');
        $this->publicKey = config('services.paystack.public');
        $this->baseUrl = config('services.paystack.baseUrl');

        if (empty($this->secretKey)) {
            throw new \RuntimeException('Paystack secret key not configured');
        }
    }

    public function initializeTransaction(array $data): array
    {
        // Validate required fields (same as you had)
        $required = ['donor_email', 'amount', 'donor_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $reference = $data['reference'] ?? 'DON_' . strtoupper(Str::random(10)) . '_' . time();

        $payload = [
            'email' => $data['donor_email'],
            'amount' => (int)($data['amount'] * 100),
            'reference' => $reference,
            'currency' => $data['currency'] ?? 'NGN',
            //'callback_url' => config('app.frontend_url'),
            'callback_url' => 'http://localhost:4200/donation',
            'metadata' => [
                'donor_name' => $data['donor_name'],
                'donor_phone' => $data['donor_phone'] ?? null,
                'purpose' => $data['purpose'] ?? 'General Donation',
                'is_anonymous' => $data['is_anonymous'] ?? false,
            ],
        ];

        // Try facade first (cleaner API). If package is not available or fails, fallback to raw HTTP.
        try {
            // If facade is available this will work; if not, it will throw and we fallback
            $facadeResponse = Paystack::transaction()->initialize($payload)->response();

            if (!empty($facadeResponse['status']) && !empty($facadeResponse['data'])) {
                return [
                    'authorization_url' => $facadeResponse['data']['authorization_url'] ?? null,
                    'access_code'       => $facadeResponse['data']['access_code'] ?? null,
                    'reference'         => $facadeResponse['data']['reference'] ?? $reference,
                ];
            }

            // If facade returned unexpected shape, fallthrough to raw HTTP fallback
            Log::warning('Paystack facade returned unexpected response', ['response' => $facadeResponse]);

        } catch (\Throwable $e) {
            // Facade not present or package call failed — log and fallback
            Log::warning('Paystack facade initialize failed, falling back to HTTP', [
                'error' => $e->getMessage(),
                'reference' => $reference
            ]);
        }

        // --- Fallback: your original Http::withToken flow ---
        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/transaction/initialize", $payload);

            if (!$response->successful()) {
                Log::error('Paystack initialization failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'reference' => $reference
                ]);
                throw new \Exception('Failed to initialize payment with Paystack');
            }

            $responseData = $response->json();

            if (empty($responseData['status']) || empty($responseData['data'])) {
                throw new \Exception($responseData['message'] ?? 'Payment initialization failed');
            }

            return [
                'authorization_url' => $responseData['data']['authorization_url'],
                'access_code' => $responseData['data']['access_code'],
                'reference' => $responseData['data']['reference']
            ];

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Paystack API request failed', [
                'error' => $e->getMessage(),
                'reference' => $reference
            ]);
            throw new \Exception('Unable to connect to payment gateway');
        }
    }

    /**
     * Verify a transaction by reference
     * Called by webhook or manual verification endpoint
     *
     * @param string $reference
     * @return array Transaction details
     * @throws \Exception
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(30)
                ->get("{$this->baseUrl}/transaction/verify/{$reference}");

            if (!$response->successful()) {
                Log::error('Paystack verification failed', [
                    'status' => $response->status(),
                    'reference' => $reference
                ]);

                throw new \Exception('Failed to verify transaction');
            }

            $responseData = $response->json();

            if (!$responseData['status']) {
                throw new \Exception($responseData['message'] ?? 'Transaction verification failed');
            }

            $data = $responseData['data'];

            // Return normalized data
            return [
                'status' => $data['status'], // success, failed, abandoned
                'reference' => $data['reference'],
                'amount' => $data['amount'] / 100, // Convert back to main currency
                'currency' => $data['currency'],
                'channel' => $data['channel'] ?? null, // card, bank, ussd, etc.
                'paid_at' => $data['paid_at'] ?? null,
                'customer_email' => $data['customer']['email'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'fees' => isset($data['fees']) ? $data['fees'] / 100 : 0,
                'authorization' => $data['authorization'] ?? null, // Card details if saved
                'raw_data' => $data // Keep full response for logging
            ];

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Paystack verification request failed', [
                'error' => $e->getMessage(),
                'reference' => $reference
            ]);

            throw new \Exception('Unable to verify transaction');
        }
    }

    /**
     * Verify webhook signature from Paystack
     * CRITICAL: Always validate webhook requests
     *
     * @param string $payload Raw request body
     * @param string|null $signature Header value
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if (empty($signature)) {
            return false;
        }

        $computedSignature = hash_hmac('sha512', $payload, $this->secretKey);

        return hash_equals($computedSignature, $signature);
    }

    /**
     * Get transaction timeline/history
     * Useful for debugging or detailed logs
     *
     * @param string $reference
     * @return array|null
     */
    public function getTransactionTimeline(string $reference): ?array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(30)
                ->get("{$this->baseUrl}/transaction/timeline/{$reference}");

            if ($response->successful()) {
                return $response->json()['data'] ?? null;
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Failed to fetch transaction timeline', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Check if transaction was successful
     *
     * @param array $transactionData
     * @return bool
     */
    public function isSuccessful(array $transactionData): bool
    {
        return isset($transactionData['status']) &&
               $transactionData['status'] === 'success';
    }

    /**
     * Get public key for frontend initialization (optional alternative flow)
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }



}
