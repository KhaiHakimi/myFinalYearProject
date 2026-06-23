<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Weather Advisory: Important Information About Your Trip</title>
    <style>
        body { font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; color: #334155; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding-bottom: 60px; padding-top: 40px;}
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .header-banner { 
            background-color: #0f172a; 
            background-image: url('{{ asset('images/email-banner.png') }}'); 
            background-size: cover; 
            background-position: center; 
            padding: 50px 20px; 
            text-align: center; 
            position: relative;
        }
        .header-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(rgba(15, 23, 42, 0.6), rgba(15, 23, 42, 0.9));
        }
        .header-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .logo { width: auto; height: 48px; margin-bottom: 10px; }
        .header-title { color: #ffffff; font-size: 24px; font-weight: 700; margin: 0; letter-spacing: -0.5px; }
        
        .content { padding: 40px; line-height: 1.6; font-size: 16px; }
        .greeting { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 20px; margin-top: 0;}
        
        .alert-box { background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 16px 20px; margin-bottom: 25px; border-radius: 0 6px 6px 0; }
        .alert-text { color: #b45309; margin: 0; font-weight: 500; font-size: 15px; }
        
        .details-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px; margin-bottom: 25px; }
        .details-title { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 700; margin-top: 0; margin-bottom: 15px; }
        .details-row { display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px; }
        .details-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .label { font-weight: 500; color: #64748b; font-size: 15px;}
        .value { font-weight: 600; color: #0f172a; font-size: 15px; text-align: right;}
        
        .info-list { font-size: 15px; color: #334155; margin-bottom: 20px;}
        .info-list ul { padding-left: 20px; margin-top: 10px; }
        .info-list li { margin-bottom: 8px; }
        
        .footer { background-color: #ffffff; padding: 30px 40px; text-align: center; font-size: 13px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
        .footer p { margin: 5px 0; }
        
        @media only screen and (max-width: 600px) {
            .wrapper { padding-top: 0; padding-bottom: 0; }
            .main { border-radius: 0; box-shadow: none; }
            .content { padding: 30px 20px; }
            .details-row { flex-direction: column; }
            .value { text-align: left; margin-top: 4px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main">
            <div class="header-banner">
                <div class="header-content">
                    <img src="{{ asset('images/logo.png') }}" alt="FerryCast Logo" class="logo" onerror="this.style.display='none'">
                    <h1 class="header-title">FerryCast</h1>
                </div>
            </div>
            
            <div class="content">
                <p class="greeting">Hi {{ $booking->passenger_name }},</p>
                
                <div class="alert-box">
                    <p class="alert-text"><strong>Weather Advisory:</strong> We are closely monitoring weather conditions for your upcoming trip. Your trip is not cancelled yet.</p>
                </div>

                <p>Current marine forecasts indicate that there may be high winds or rough seas at the time of your departure. We wanted to give you an early heads-up.</p>
                
                <div class="details-box">
                    <h3 class="details-title">Trip Details</h3>
                    <div class="details-row">
                        <span class="label">Reference</span>
                        <span class="value">{{ $booking->booking_reference }}</span>
                    </div>
                    <div class="details-row">
                        <span class="label">Route</span>
                        <span class="value">{{ $schedule->origin->name }} &rarr; {{ $schedule->destination->name }}</span>
                    </div>
                    <div class="details-row">
                        <span class="label">Departure</span>
                        <span class="value">{{ $schedule->departure_time->format('d M Y, h:i A') }}</span>
                    </div>
                </div>

                <div class="info-list">
                    <strong>What you need to know:</strong>
                    <ul>
                        <li>Your trip is <strong>not cancelled yet</strong>.</li>
                        <li>We will continue to monitor the forecast.</li>
                        <li>A final safety decision will be made and communicated to you <strong>12 hours</strong> before your scheduled departure.</li>
                        <li>If your trip is cancelled, you will automatically receive a full refund.</li>
                    </ul>
                </div>
                
                <p>We advise you to keep an eye on your email for further updates.</p>
            </div>
            
            <div class="footer">
                <p>&copy; {{ date('Y') }} FerryCast. Safe travels.</p>
                <p>If you have any questions, please reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
