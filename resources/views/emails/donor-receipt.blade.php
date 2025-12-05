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
        .receipt-row.total { font-weight: bold; font-size: 1.2em; border-bottom: none; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 0.9em; }
        .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thank You for Your Donation!</h1>
        </div>

        <div class="content">
            <p>Dear {{ $donation->donor_name }},</p>

            <p>We are deeply grateful for your generous donation. Your support helps us continue our mission and make a positive impact in our community.</p>

            <div class="receipt-box">
                <h2>Donation Receipt</h2>

                <div class="receipt-row">
                    <span>Reference Number:</span>
                    <strong>{{ $donation->reference }}</strong>
                </div>

                <div class="receipt-row">
                    <span>Date:</span>
                    <strong>{{ $donation->paid_at->format('F d, Y h:i A') }}</strong>
                </div>

                <div class="receipt-row">
                    <span>Payment Method:</span>
                    <strong>{{ ucfirst($donation->payment_channel ?? 'Online Payment') }}</strong>
                </div>

                @if($donation->purpose)
                <div class="receipt-row">
                    <span>Purpose:</span>
                    <strong>{{ $donation->purpose }}</strong>
                </div>
                @endif

                <div class="receipt-row total">
                    <span>Amount Donated:</span>
                    <strong>{{ $donation->currency }} {{ number_format($donation->amount, 2) }}</strong>
                </div>
            </div>

            <p>This email serves as your official receipt for tax purposes. Please keep it for your records.</p>

            <p>If you have any questions about your donation, please don't hesitate to contact us.</p>

            <p>With sincere gratitude,<br>
            <strong>{{ config('app.name') }} Team</strong></p>
        </div>

        <div class="footer">
            <p>This is an automated receipt. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
