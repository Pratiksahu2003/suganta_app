{{ config('app.name') }} - Email Verification

Hello {{ $notifiable->name }}!

Thank you for creating your account on {{ config('app.name') }}! We're excited to have you join our learning community.

To complete your registration and start exploring our platform, please verify your email address by clicking the link below:

{{ $verificationUrl }}

What happens next?
After verifying your email, you'll have full access to our platform including personalized learning experiences, expert tutors, and comprehensive educational resources.

IMPORTANT: This verification link will expire in {{ config('auth.verification.expire', 60) }} minutes for security reasons.

Platform Features:
🎓 Expert Tutors - Connect with verified educators
📚 Rich Content - Access comprehensive materials  
🚀 Fast Learning - Accelerate your progress

If you did not create an account on {{ config('app.name') }}, please ignore this email. No further action is required.

If you're having trouble clicking the verification link, copy and paste the URL above into your web browser.

Best regards,
{{ config('app.name') }} Team

Visit our website: {{ config('app.url') }}
Contact Support: {{ config('app.contact_url') }}
Help Center: {{ config('app.help_center_url') }}

This email was sent to {{ $notifiable->email }}. If you have any questions, please contact our support team.
