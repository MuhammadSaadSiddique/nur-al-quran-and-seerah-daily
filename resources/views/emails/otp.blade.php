<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f1f5f9;
        }

        .container {
            max-width: 480px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .header {
            background: linear-gradient(135deg, #065f46, #047857);
            padding: 40px 32px;
            text-align: center;
        }

        .header h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 0 0 4px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .header p {
            color: #a7f3d0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-weight: 700;
            margin: 0;
        }

        .body {
            padding: 40px 32px;
            text-align: center;
        }

        .body p {
            color: #475569;
            font-size: 15px;
            line-height: 1.7;
            margin: 0 0 24px;
        }

        .otp-box {
            background: #f0fdf4;
            border: 2px solid #bbf7d0;
            border-radius: 16px;
            padding: 24px;
            margin: 24px 0;
        }

        .otp-code {
            font-size: 40px;
            font-weight: 900;
            color: #065f46;
            letter-spacing: 12px;
            font-family: 'Courier New', monospace;
            margin: 0;
        }

        .otp-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #6b7280;
            font-weight: 700;
            margin-top: 8px;
        }

        .warning {
            background: #fefce8;
            border: 1px solid #fef08a;
            border-radius: 12px;
            padding: 16px;
            margin-top: 24px;
        }

        .warning p {
            color: #92400e;
            font-size: 13px;
            margin: 0;
            font-weight: 600;
        }

        .footer {
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
        }

        .footer p {
            color: #94a3b8;
            font-size: 12px;
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>The Eternal Echo</h1>
            <p>Spiritual Knowledge Journey</p>
        </div>
        <div class="body">
            <p>Assalamu Alaikum! Here is your verification code to access your spiritual learning journey:</p>

            <div class="otp-box">
                <p class="otp-code">{{ $otp }}</p>
                <p class="otp-label">Verification Code</p>
            </div>

            <p>Enter this code in the app to continue. This code will expire in <strong>5 minutes</strong>.</p>

            <div class="warning">
                <p>⚠️ If you did not request this code, please ignore this email.</p>
            </div>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} The Eternal Echo</p>
        </div>
    </div>
</body>

</html>