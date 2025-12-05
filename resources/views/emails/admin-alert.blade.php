<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #059669; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .alert-box { background: white; border-left: 4px solid #059669; padding: 20px; margin: 20px 0; }
        .detail-row { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #6b7280; display: inline-block; width: 150px; }
        .amount { font-size: 1.5em; color: #059669; font-weight: bold; text-align: center; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 New Donation Received!</h1>
        </div>

        <div class="content">
            <div class="amount">
                {{ $donation->currency }} {{ number_format($donation->amount, 2) }}
            </div>

            <div class="alert-box">
                <h2>Donation Details</h2>

                <div class="detail-row">
                    <span class="label">Reference:</span>
                    <span>{{ $donation->reference }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Donor Name:</span>
                    <span>{{ $donation->is_anonymous ? 'Anonymous' : $donation->donor_name }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Email:</span>
                    <span>{{ $donation->donor_email }}</span>
                </div>

                @if($donation->donor_phone)
                <div class="detail-row">
                    <span class="label">Phone:</span>
                    <span>{{ $donation->donor_phone }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="label">Amount:</span>
                    <span>{{ $donation->currency }} {{ number_format($donation->amount, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Payment Channel:</span>
                    <span>{{ ucfirst($donation->payment_channel ?? 'N/A') }}</span>
                </div>

                @if($donation->purpose)
                <div class="detail-row">
                    <span class="label">Purpose:</span>
                    <span>{{ $donation->purpose }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="label">Date & Time:</span>
                    <span>{{ $donation->paid_at->format('F d, Y h:i A') }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Paystack Ref:</span>
                    <span>{{ $donation->paystack_reference }}</span>
                </div>
            </div>

            <p><em>This is an automated notification from your donation system.</em></p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} Admin Panel</p>
        </div>
    </div>
</body>
</html>
