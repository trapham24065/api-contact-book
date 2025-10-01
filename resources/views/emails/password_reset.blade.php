<!DOCTYPE html>
<html>
<head>
    <title>Reset Your Password</title>
</head>
<body>
<p>Hi {{ $name }},</p>
<p>We received a request to reset the password for your account.</p>
<p>Click the link below to set a new password:</p>
<p><a href="{{ $resetLink }}">{{ $resetLink }}</a></p>
<p>This link is valid for 20 minutes and can be used only once. If you didn’t request this, you can safely ignore this
    email.</p>
<br>
<p>Thanks,</p>
<p>The {{ config('app.name') }} Team</p>
</body>
</html>
