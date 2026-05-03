<!DOCTYPE html>
<html>
<head>
    <style>
        /* Reset and Base Styles */
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f5f7;
            margin: 0;
            padding: 40px 20px;
            color: #333333;
            line-height: 1.6;
        }

        /* Main Card Container */
        .box {
            background: #ffffff;
            max-width: 500px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05); /* Subtle depth */
            border-top: 6px solid #1a73e8; /* Brand accent color */
        }

        /* Typography */
        h2 {
            margin-top: 0;
            color: #1f2937;
            font-size: 24px;
        }

        p {
            color: #4b5563;
            font-size: 16px;
            margin-bottom: 20px;
        }

        strong {
            color: #1f2937;
        }

        /* OTP Display Section */
        .otp-container {
            text-align: center;
            margin: 32px 0;
        }

        .otp {
            display: inline-block;
            font-size: 40px;
            font-weight: 800;
            letter-spacing: 12px;
            color: #1a73e8;
            background: #f0f7ff;
            padding: 20px 24px 20px 36px; /* Offset padding to balance letter-spacing */
            border-radius: 8px;
            border: 2px dashed #bbd6fe; /* Dashed border draws focus */
        }

        /* Footer & Security Note */
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 32px 0 24px;
        }

        .note {
            color: #9ca3af;
            font-size: 14px;
            text-align: center;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Hello {{ $userName }},</h2>
        <p>We received a request to reset your password. Please use the One-Time Password (OTP) below to complete the process:</p>

        <div class="otp-container">
            <div class="otp">{{ $otp }}</div>
        </div>

        <p>This code will expire in <strong>15 minutes</strong> for your security.</p>

        <div class="divider"></div>

        <p class="note">If you did not request a password reset, you can safely ignore this email. Your account remains secure.</p>
    </div>
</body>
</html>
