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
            margin-bottom: 8px;
        }

        .body p.intro {
            color: #475569;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .analysis-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .analysis-card h3 {
            color: #0f172a;
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .lens-badge {
            display: inline-block;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 10px;
            border-radius: 9999px;
            margin-bottom: 12px;
        }

        .analysis-content {
            color: #334155;
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 16px;
        }

        .meta {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            margin-bottom: 16px;
        }

        .btn {
            display: inline-block;
            background: #059669;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #047857;
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
            <p>Weekly Research Highlights</p>
        </div>
        <div class="body">
            <h2>Assalamu Alaikum {{ $user->display_name ?: explode('@', $user->email)[0] }}!</h2>
            <p class="intro">Here are the newly published Quranic research connections and lens analyses mapped in the last 7 days. We hope these insights inspire and enhance your study journey:</p>

            @foreach($analyses as $analysis)
                <div class="analysis-card">
                    <span class="lens-badge">{{ strtoupper($analysis->lens_type) }} LENS</span>
                    <h3>{{ $analysis->title }}</h3>
                    <p class="analysis-content">
                        {{ Str::limit(strip_tags($analysis->content), 250) }}
                    </p>
                    <div class="meta">
                        By {{ $analysis->user ? ($analysis->user->display_name ?: explode('@', $analysis->user->email)[0]) : 'System' }}
                        (Approved by: {{ $analysis->moderator ? ($analysis->moderator->display_name ?: $analysis->moderator->name ?: explode('@', $analysis->moderator->email)[0]) : 'Admin' }})
                    </div>
                    <a href="{{ url('/' . $analysis->chapter_number . '/' . $analysis->verse_number) }}" class="btn">
                        View Verse Context
                    </a>
                </div>
            @endforeach
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} The Eternal Echo. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
