<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #111;">
    <p>Use this one-time sign-in code (expires in 15 minutes):</p>
    <p style="font-size: 28px; font-weight: bold; letter-spacing: 0.2em;">{{ $code }}</p>
    <p>Or open this link on the same device:</p>
    <p><a href="{{ $url }}">{{ $url }}</a></p>
    <p style="color: #666; font-size: 14px;">If you did not request this, you can ignore this email.</p>
</body>
</html>
