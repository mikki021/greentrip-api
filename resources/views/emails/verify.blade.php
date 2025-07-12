<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email Address</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to GreenTrip!</h1>
    </div>

    <div class="content">
        <h2>Hello {{ $user->name }},</h2>

        <p>Thank you for registering with GreenTrip. To complete your registration, please verify your email address by clicking the button below:</p>

        <div style="text-align: center;">
            <a href="{{ url('/api/verify-email/' . $token) }}" class="button">Verify Email Address</a>
        </div>

        <p>This verification link will expire on <strong>{{ $expiresAt->format('F j, Y \a\t g:i A') }}</strong> (48 hours from now).</p>

        <p>If you did not create an account, no further action is required.</p>

        <p>If you're having trouble clicking the button, copy and paste this URL into your browser:</p>
        <p style="word-break: break-all; color: #666;">{{ url('/api/verify-email/' . $token) }}</p>
    </div>

    <div class="footer">
        <p>This email was sent to {{ $user->email }}. If you have any questions, please contact our support team.</p>
        <p>&copy; {{ date('Y') }} GreenTrip. All rights reserved.</p>
    </div>
</body>
</html>