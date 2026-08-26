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
            max-width: 600px;
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
            font-size: 26px;
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
        }

        .body h2 {
            color: #1e293b;
            font-size: 18px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 16px;
        }

        .body p {
            color: #475569;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 24px;
            white-space: pre-line;
        }

        .footer {
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            background: #fafafa;
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
            <p>Official Announcement</p>
        </div>
        <div class="body">
            <h2>Assalamu Alaikum {{ $user->display_name ?: explode('@', $user->email)[0] }}!</h2>
            <p>{{ $announcementContent }}</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} The Eternal Echo. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
