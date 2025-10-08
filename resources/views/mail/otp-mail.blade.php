<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verification Code - Hovertask</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #3B82F6;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
            text-align: center;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #3B82F6;
            letter-spacing: 8px;
            border: 2px solid #3B82F6;
            padding: 15px 20px;
            border-radius: 8px;
            display: inline-block;
            margin: 30px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Email Verification Code</h1>
        </div>
        <div class="content">
            <h2>Hello {{ $firstName }},</h2>
            <p>Welcome to Hovertask! Please use the following 6-digit code to verify your email address:</p>
            
            <div class="otp-code">
                {{ $otp }}
            </div>
            
            <p>This code will expire in 10 minutes for security purposes.</p>
            
            <p><strong>Important:</strong></p>
            <ul style="text-align: left; display: inline-block;">
                <li>Do not share this code with anyone</li>
                <li>This code is only valid for this registration session</li>
                <li>If you didn't request this code, please ignore this email</li>
            </ul>
            
            <p>Thanks for joining Hovertask!</p>
        </div>
        <div class="footer">
            <p>Best regards,<br>The Hovertask Team</p>
        </div>
    </div>
</body>
</html> 