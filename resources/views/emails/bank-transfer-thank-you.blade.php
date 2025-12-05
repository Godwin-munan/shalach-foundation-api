<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .receipt-box { background: white; border: 1px solid #e5e7eb; padding: 20px; margin: 20px 0; }
        .receipt-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .receipt-row.total { font-weight: bold; font-size: 1.05em; border-bottom: none; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 0.9em; }
        .account-box { background: #ffffff; border: 1px solid #e5e7eb; padding: 15px; margin-top: 15px; }
        .account-section { margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed #eef2f7; }
        .account-label { font-weight: 700; color: #111827; margin-bottom: 6px; display:block; }
        .muted { color: #6b7280; font-size: 0.95em; }
        .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .mono { font-family: monospace; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thank You for Your Donation!</h1>
        </div>

        <div class="content">
            <p>Hi {{ $donation->donor_name ?? 'Friend' }},</p>

            <p>Thank you, we’ve received your donation request. Please transfer the donation amount to one of the account options shown below. We appreciate your support and will send you an official confirmation email once the transfer has been verified.</p>

            <div class="receipt-box">
                <h2>Donation Receipt (Pending Confirmation)</h2>

                <div class="receipt-row">
                    <span>Reference Number:</span>
                    <strong>{{ $donation->reference ?? '—' }}</strong>
                </div>

                <div class="receipt-row">
                    <span>Payment Method:</span>
                    <strong>{{ ucfirst($donation->payment_channel ?? 'Online Payment') }}</strong>
                </div>

                @if(!empty($donation->purpose))
                <div class="receipt-row">
                    <span>Purpose:</span>
                    <strong>{{ $donation->purpose }}</strong>
                </div>
                @endif

                <div class="receipt-row total">
                    <span>Amount:</span>
                    <strong>{{ $donation->currency ?? 'NGN' }} {{ number_format($donation->amount ?? 0, 2) }}</strong>
                </div>

                @if( ($donation->payment_channel ?? '') === 'bank_transfer' )
                    <div class="account-box">
                        <h3>Bank Transfer Accounts</h3>

                        {{-- Resolve the two accounts: prefer $bankDetails passed to view, otherwise use config('donations') --}}
                        @php
                            $naira = $bankDetails['naira'] ?? config('donations.naira', []);
                            $dollar = $bankDetails['dollar'] ?? config('donations.dollar', []);
                        @endphp

                        <div class="account-section">
                            <div class="account-label">Naira Account</div>
                            <div><strong>Bank:</strong> {{ $naira['bank_name'] ?? 'UBA' }}</div>
                            <div><strong>Account Name:</strong> {{ $naira['account_name'] ?? 'Shalach Empowerment Foundation' }}</div>
                            <div><strong>Account Number:</strong> <span class="mono">{{ $naira['account_number'] ?? '1028497609' }}</span></div>
                            @if(!empty($naira['account_type'])) <div><strong>Type:</strong> {{ $naira['account_type'] }}</div> @endif
                        </div>

                        <div class="account-section">
                            <div class="account-label">Dollar Account</div>
                            <div><strong>Bank:</strong> {{ $dollar['bank_name'] ?? 'UBA' }}</div>
                            <div><strong>Account Name:</strong> {{ $dollar['account_name'] ?? 'Shalach Empowerment Foundation' }}</div>
                            <div><strong>Account Number:</strong> <span class="mono">{{ $dollar['account_number'] ?? '3004885585' }}</span></div>
                            @if(!empty($dollar['account_type'])) <div><strong>Type:</strong> {{ $dollar['account_type'] }}</div> @endif
                        </div>

                        @if(!empty($bankDetails['narration'] ?? config('donations.narration')))
                        <div style="margin-top:10px;">
                            <strong>Narration:</strong>
                            <div class="muted">{{ $bankDetails['narration'] ?? config('donations.narration') }}</div>
                        </div>
                        @endif

                        <p class="muted" style="margin-top:12px;">
                            After you transfer, please keep your transfer confirmation/receipt. We will verify the payment and email you confirmation once it’s processed.
                        </p>
                    </div>
                @else
                    <p class="muted" style="margin-top:12px;">
                        If you did not select bank transfer, no action is required. We will send a confirmation once the payment is completed.
                    </p>
                @endif
            </div>

            <p>If you have questions or need help, reply to this message or contact us at <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>

            <p>With gratitude,<br>
            <strong>{{ config('app.name') }} Team</strong></p>
        </div>

        <div class="footer">
            <p class="muted">This is an automated message. Please do not reply to this email unless stated otherwise.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
