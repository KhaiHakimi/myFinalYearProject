<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your FerryCast Verification Code</title>
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
        
        .content { padding: 40px; line-height: 1.6; font-size: 16px; text-align: center;}
        .greeting { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 20px; margin-top: 0;}
        
        .code-box { 
            background-color: #f1f5f9; 
            border: 2px dashed #94a3b8; 
            border-radius: 8px; 
            padding: 25px; 
            margin: 30px auto; 
            max-width: 300px;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 4px;
            color: #0284c7;
        }
        
        .footer { background-color: #ffffff; padding: 30px 40px; text-align: center; font-size: 13px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
        .footer p { margin: 5px 0; }
        
        @media only screen and (max-width: 600px) {
            .wrapper { padding-top: 0; padding-bottom: 0; }
            .main { border-radius: 0; box-shadow: none; }
            .content { padding: 30px 20px; }
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
                <p class="greeting">Welcome to FerryCast!</p>
                
                <p>Thank you for registering. Please use the following verification code to confirm your email address and activate your account.</p>
                
                <div class="code-box">
                    {{ $code }}
                </div>
                
                <p style="color: #64748b; font-size: 14px;">If you did not create an account, you can safely ignore this email.</p>
            </div>
            
            <div class="footer">
                <p>&copy; {{ date('Y') }} FerryCast. Safe travels.</p>
                <p>This is an automated message, please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
