<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #111;">
    <p>Hi {{ $user->name }},</p>
    <p>Please verify your email address for {{ config('app.name') }} by clicking the link below:</p>
    <p><a href="{{ $url }}">{{ $url }}</a></p>
    <p style="color: #666; font-size: 14px;">If you did not create an account, you can ignore this email.</p>
</body>
</html>
