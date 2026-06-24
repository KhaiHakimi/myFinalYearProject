<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #1e3a8a; /* blue-900 */
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header p {
            margin: 10px 0 0;
            color: #bfdbfe; /* blue-200 */
            font-size: 14px;
        }
        .content {
            padding: 40px 30px;
        }
        .qr-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px dashed #e2e8f0;
        }
        .qr-code {
            width: 150px;
            height: 150px;
            border: 4px solid #f1f5f9;
            border-radius: 8px;
            padding: 5px;
        }
        .booking-ref {
            font-family: monospace;
            font-size: 18px;
            font-weight: 900;
            color: #334155;
            letter-spacing: 2px;
            margin-top: 15px;
        }
        .details-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .details-grid td {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .details-grid td:first-child {
            color: #64748b;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            width: 40%;
        }
        .details-grid td:last-child {
            color: #0f172a;
            font-weight: 800;
            font-size: 15px;
            text-align: right;
        }
        .price-row td {
            font-size: 18px !important;
            color: #059669 !important; /* emerald-600 */
            border-bottom: none !important;
            padding-top: 20px !important;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ferrycast Ticket</h1>
            <p>Your booking has been successfully confirmed.</p>
        </div>
        
        <div class="content">
            <div class="qr-section">
                <!-- Using a free QR code generation API -->
                <img class="qr-code" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($booking->booking_reference) }}" alt="QR Code">
                <div class="booking-ref">{{ $booking->booking_reference }}</div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 5px;">Show this QR code at the terminal</div>
            </div>

            <table class="details-grid">
                <tr>
                    <td>Passenger Name</td>
                    <td>{{ $booking->passenger_name }}</td>
                </tr>
                <tr>
                    <td>Tickets</td>
                    <td>{{ $booking->quantity }} Passenger(s)</td>
                </tr>
                <tr>
                    <td>Vessel</td>
                    <td>{{ $booking->schedule->ferry->name }}</td>
                </tr>
                <tr>
                    <td>Origin</td>
                    <td>{{ $booking->schedule->origin->name }}</td>
                </tr>
                <tr>
                    <td>Destination</td>
                    <td>{{ $booking->schedule->destination->name }}</td>
                </tr>
                <tr>
                    <td>Departure</td>
                    <td>
                        {{ \Carbon\Carbon::parse($booking->schedule->departure_time)->format('d M Y') }}<br>
                        <span style="color: #3b82f6;">{{ \Carbon\Carbon::parse($booking->schedule->departure_time)->format('H:i') }}</span>
                    </td>
                </tr>
                <tr class="price-row">
                    <td>Total Paid</td>
                    <td>RM {{ number_format($booking->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Please arrive at the terminal at least 30 minutes before departure.</p>
            <p>&copy; {{ date('Y') }} Ferrycast Ticketing. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
