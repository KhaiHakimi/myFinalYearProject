<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Urgent: Your Ferry Booking has been Cancelled Due to Weather</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 20px; color: #1f2937; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .header { background-color: #dc2626; padding: 30px 20px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .content { padding: 30px; line-height: 1.6; }
        .details-box { background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 20px; margin-top: 20px; }
        .details-row { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
        .details-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .label { font-weight: bold; color: #6b7280; }
        .value { font-weight: 600; color: #111827; }
        .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 14px; color: #6b7280; border-top: 1px solid #e5e7eb; }
        .btn { display: inline-block; background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Booking Cancelled: Severe Weather Alert</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $booking->passenger_name }},</p>
            
            <p>We are writing to urgently inform you that your upcoming ferry departure has been <strong>automatically cancelled</strong> due to severe weather conditions detected by our marine safety systems.</p>

            <p>At FerryCast, your safety is our top priority. Our AI monitoring system has flagged the route as hazardous for travel (e.g., high waves or strong winds).</p>
            
            <div class="details-box">
                <div class="details-row">
                    <span class="label">Booking Reference:</span>
                    <span class="value">{{ $booking->booking_reference }}</span>
                </div>
                <div class="details-row">
                    <span class="label">Route:</span>
                    <span class="value">{{ $schedule->origin->name }} to {{ $schedule->destination->name }}</span>
                </div>
                <div class="details-row">
                    <span class="label">Departure Time:</span>
                    <span class="value">{{ $schedule->departure_time->format('d M Y, h:i A') }}</span>
                </div>
                <div class="details-row">
                    <span class="label">Tickets:</span>
                    <span class="value">{{ $booking->quantity }}</span>
                </div>
            </div>

            <p><strong>Refund Information:</strong><br>
            Your payment of {{ $booking->currency }} {{ $booking->total_amount }} has been marked for a full refund. Depending on your payment provider, it may take 5-10 business days for the funds to appear in your account.</p>

            <center>
                <a href="{{ url('/') }}" class="btn">Book Another Trip</a>
            </center>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} FerryCast. All rights reserved.</p>
            <p>If you have any questions, please contact our support team.</p>
        </div>
    </div>
</body>
</html>
