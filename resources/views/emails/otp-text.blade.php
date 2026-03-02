@if($type === 'email_verification')
{{ config('app.name') }} - Email Verification Code

Hello {{ $notifiable->name ?? 'User' }}!

You've requested to verify your email address for your {{ config('app.name') }} account. Use the verification code below to complete your email verification:

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    YOUR VERIFICATION CODE: {{ $otp }}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⏱️ This code expires in 10 minutes

SECURITY NOTICE:
For your security, never share this code with anyone. {{ config('app.name') }} staff will never ask for your verification code. If you didn't request this code, please ignore this email or contact our support team immediately.

NEED HELP?
• Enter this code on the verification page to verify your email address
• Make sure you're using the correct email address
• If the code doesn't work, request a new one
• Contact support if you continue to have issues

Didn't request this code?
If you didn't request email verification, please ignore this email. Your account remains secure, and no changes have been made.

If you're having trouble verifying your email or have security concerns, please contact our support team immediately.
@else
{{ config('app.name') }} - Login Verification Code

Hello {{ $notifiable->name ?? 'User' }}!

You've requested to sign in to your {{ config('app.name') }} account. Use the verification code below to complete your login:

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    YOUR VERIFICATION CODE: {{ $otp }}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⏱️ This code expires in 5 minutes

SECURITY NOTICE:
For your security, never share this code with anyone. {{ config('app.name') }} staff will never ask for your verification code. If you didn't request this code, please ignore this email or contact our support team immediately.

NEED HELP?
• Enter this code on the login page to complete your sign-in
• Make sure you're using the correct email or phone number
• If the code doesn't work, request a new one
• Contact support if you continue to have issues

Didn't request this code?
If you didn't attempt to sign in, please ignore this email. Your account remains secure, and no changes have been made.

If you're having trouble signing in or have security concerns, please contact our support team immediately.
@endif

Best regards,
{{ config('app.name') }} Security Team

Visit our website: {{ config('app.url') }}
Contact Support: {{ config('app.url') }}/contact
Help Center: {{ config('app.url') }}/help

This email was sent to {{ $notifiable->email }}. If you have any questions or concerns about your account security, please contact our support team.

