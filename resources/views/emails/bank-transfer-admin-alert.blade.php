<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { background: #059669; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .alert-box { background: white; border-left: 4px solid #059669; padding: 20px; margin: 20px 0; }
        .detail-row { padding: 10px 0; border-bottom: 1px solid #e5e7eb; display:flex; justify-content:space-between; }
        .detail-row .label { font-weight: 600; color: #6b7280; width: 180px; }
        .detail-row .value { flex:1; text-align:right; }
        .amount { font-size: 1.6em; color: #059669; font-weight: 700; text-align: center; margin: 8px 0 18px; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 0.9em; }
        .button { display: inline-block; background: #059669; color: white; padding: 10px 18px; text-decoration: none; border-radius: 6px; margin-top: 14px; }
        .muted { color: #6b7280; font-size: 0.95em; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Donation Received</h1>
        </div>

        <div class="content">
            <div class="amount">
                {{ $donation->currency }} {{ number_format($donation->amount, 2) }}
            </div>

            <div class="alert-box">
                <h2>Donation Details (Pending Confirmation)</h2>

                <div class="detail-row">
                    <span class="label">Reference</span>
                    <span class="value">{{ $donation->reference }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Donor</span>
                    <span class="value">{{ $donation->is_anonymous ? 'Anonymous' : ($donation->donor_name ?? '—') }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Email</span>
                    <span class="value">{{ $donation->donor_email ?? '—' }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Phone</span>
                    <span class="value">{{ $donation->donor_phone ?? '—' }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Payment Channel</span>
                    <span class="value">{{ ucfirst($donation->payment_channel ?? 'N/A') }}</span>
                </div>

                @if($donation->purpose)
                <div class="detail-row">
                    <span class="label">Purpose</span>
                    <span class="value">{{ $donation->purpose }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="label">Paystack Ref</span>
                    <span class="value">{{ $donation->paystack_reference ?? '—' }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Recorded At</span>
                    <span class="value">{{ optional($donation->created_at)->format('F j, Y g:ia') ?? '—' }}</span>
                </div>

                @if( ($donation->payment_channel ?? '') === 'bank_transfer' )
                    <div style="margin-top:14px; padding-top:10px; border-top:1px solid #eef2f7;">
                        <p class="muted">
                            A bank transfer was initiated for this donation. The donation remains <strong>pending confirmation</strong>.
                            Please verify the transfer once it appears on the account, then update the donation to <em>success</em>.
                        </p>

                        <h3 style="margin:12px 0 8px;">Bank Transfer Details</h3>

                        <div class="detail-row">
                            <span class="label">Account Name</span>
                            <span class="value">{{ config('donations.account_name') ?? 'SHALACH FOUNDATION' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Account Number</span>
                            <span class="value">{{ config('donations.account_number') ?? '0000000000' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Bank</span>
                            <span class="value">{{ config('donations.bank_name') ?? 'Bank Name' }}</span>
                        </div>

                        <p class="muted" style="margin-top:12px;">
                            After confirming the transfer, mark the donation as <strong>success</strong> in the admin panel so the donor receives their confirmation email.
                        </p>
                    </div>
                @else
                    <p class="muted">
                        Payment was attempted through {{ ucfirst($donation->payment_channel ?? 'N/A') }}. Please review if any manual checks are required.
                    </p>
                @endif

                <div style="text-align:center; margin-top:18px;">
                    <a class="button" href="{{ rtrim(config('app.url'), '/') }}/admin/donations/{{ $donation->id }}">View in Admin Panel</a>
                </div>
            </div>

            <p class="muted"><em>This is an automated notification from the donation system.</em></p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} Admin Panel</p>
        </div>
    </div>
</body>
</html>
