<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $subject ?? 'SuGanta Tutors' }} - SuGanta Tutors</title>
   
    <style type="text/css">
        /* Client-specific Styles */
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-padding { padding: 20px 15px !important; }
            .header-padding { padding: 30px 20px !important; }
            .footer-padding { padding: 30px 20px !important; }
            .social-icon { width: 36px !important; height: 36px !important; line-height: 36px !important; }
            .font-size-small { font-size: 12px !important; }
        }
    </style>
    <!--<![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
    <!-- Wrapper Table -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8f9fa; margin: 0; padding: 0;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <!--[if mso]>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600">
                <tr>
                <td>
                <![endif]-->
                <!-- Main Container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    
                    <!-- Header Section -->
                    <tr>
                        <td style="background-color: #a8c0ff; padding: 40px 30px; text-align: center;" class="header-padding">
                            <!--[if mso]>
                            <v:rect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" fill="t">
                                <v:fill type="frame" src="" color="#a8c0ff"/>
                                <w:anchorlock/>
                            <![endif]-->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-bottom: 20px;">
                                        <!-- Logo Container -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                                            <tr>
                                                <td align="center" style="width: 160px; height: 64px; padding: 4px;">
                                                    <img src="{{ asset('logo/Su250.png') }}" alt="{{ config('app.name', 'SuGanta Tutors') }} Logo" style="max-width: 160px; width: 100%; height: auto; display: block; border: 0;" />
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                               
                                <tr>
                                    <td align="center">
                                        <p style="margin: 0; font-size: 14px; color: rgba(255, 255, 255, 0.95); line-height: 1.5;">
                                            Connecting Students with Expert Tutors
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <!--[if mso]>
                            </v:rect>
                            <![endif]-->
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px 30px;" class="content-padding">
                            <!-- Greeting -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding-bottom: 20px;">
                                        <p style="margin: 0; font-size: 18px; color: #4a5568; font-weight: 500; line-height: 1.4;">
                                            Hello {{ (!empty($user) && !empty($user->name)) ? $user->name : 'Valued Customer' }},
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Message Content -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding-bottom: 30px;">
                                        <div style="color: #5a6c7d; font-size: 16px; line-height: 1.8; text-align: left;">
                                            {!! $content ?? '' !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Help Section -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="background-color: #f0f4ff; border-left: 4px solid #a8c0ff; padding: 20px; border-radius: 4px;">
                                        <p style="margin: 0 0 8px 0; color: #5a6c7d; font-size: 15px; font-weight: 600; line-height: 1.4;">
                                            Need Help?
                                        </p>
                                        <p style="margin: 0; color: #7a8a9a; font-size: 14px; line-height: 1.6;">
                                            If you have any questions or concerns, please don't hesitate to contact us. We're here to help!
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #e8ecf1; padding: 40px 30px;" class="footer-padding">
                            <!-- Company Info -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-bottom: 25px;">
                                        <h4 style="margin: 0; font-size: 18px; font-weight: 600; color: #4a5568; line-height: 1.3;">
                                            SuGanta Tutors
                                        </h4>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom: 5px;">
                                        <p style="margin: 0; font-size: 14px; color: #6b7280; line-height: 1.5;">
                                            Connecting students with expert tutors for personalized learning experiences.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Contact Information -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 25px 0;">
                                <tr>
                                    <td align="center" style="padding: 15px 0; border-top: 1px solid rgba(0, 0, 0, 0.08); border-bottom: 1px solid rgba(0, 0, 0, 0.08);">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            @if(!empty($companyInfo) && !empty($companyInfo['email']))
                                            <tr>
                                                <td align="center" style="padding: 8px 0;">
                                                    <a href="mailto:{{ $companyInfo['email'] }}" style="color: #5b9bd5; text-decoration: none; font-size: 14px; line-height: 1.5;">
                                                        <span style="color: #5b9bd5; font-weight: 500;">Email:</span> {{ $companyInfo['email'] }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(!empty($companyInfo) && !empty($companyInfo['phone']))
                                            <tr>
                                                <td align="center" style="padding: 8px 0;">
                                                    <a href="tel:{{ str_replace(['-', ' ', '(', ')'], '', $companyInfo['phone']) }}" style="color: #5b9bd5; text-decoration: none; font-size: 14px; line-height: 1.5;">
                                                        <span style="color: #5b9bd5; font-weight: 500;">Phone:</span> {{ $companyInfo['phone'] }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(!empty($companyInfo) && !empty($companyInfo['website']))
                                            <tr>
                                                <td align="center" style="padding: 8px 0;">
                                                    <a href="{{ $companyInfo['website'] }}" target="_blank" rel="noopener noreferrer" style="color: #5b9bd5; text-decoration: none; font-size: 14px; line-height: 1.5;">
                                                        <span style="color: #5b9bd5; font-weight: 500;">Website:</span> 
                                                        @php
                                                            $parsedUrl = @parse_url($companyInfo['website']);
                                                            if (!empty($parsedUrl) && !empty($parsedUrl['host'])) {
                                                                $host = $parsedUrl['host'];
                                                            } else {
                                                                $host = str_replace(['http://', 'https://', 'www.'], '', $companyInfo['website']);
                                                                $host = rtrim($host, '/');
                                                            }
                                                        @endphp
                                                        {{ $host }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(!empty($companyInfo) && !empty($companyInfo['address']) && is_array($companyInfo['address']))
                                            <tr>
                                                <td align="center" style="padding: 12px 0 8px 0;">
                                                    <p style="margin: 0; color: #6b7280; font-size: 13px; line-height: 1.7;">
                                                        <span style="color: #4a5568; font-weight: 500;">Address:</span><br>
                                                        @if(!empty($companyInfo['address']['line1']))
                                                            {{ $companyInfo['address']['line1'] }}@if(!empty($companyInfo['address']['line2'])), @endif<br>
                                                        @endif
                                                        @if(!empty($companyInfo['address']['line2']))
                                                            {{ $companyInfo['address']['line2'] }}@if(!empty($companyInfo['address']['city'])), @endif<br>
                                                        @endif
                                                        @if(!empty($companyInfo['address']['city']))
                                                            {{ $companyInfo['address']['city'] }}@if(!empty($companyInfo['address']['state'])), @endif
                                                        @endif
                                                        @if(!empty($companyInfo['address']['state']))
                                                            {{ $companyInfo['address']['state'] }}
                                                        @endif
                                                        @if(!empty($companyInfo['address']['pincode']))
                                                            {{ $companyInfo['address']['pincode'] }}
                                                        @endif
                                                        @if(!empty($companyInfo['address']['country']))
                                                            <br>{{ $companyInfo['address']['country'] }}
                                                        @endif
                                                    </p>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Social Media Icons -->
                            @php
                                $hasSocialLinks = false;
                                if (!empty($companyInfo) && !empty($companyInfo['social']) && is_array($companyInfo['social'])) {
                                    $activeSocialLinks = array_filter($companyInfo['social'], function($url) {
                                        return !empty($url) && trim($url) !== '' && $url !== '#';
                                    });
                                    $hasSocialLinks = count($activeSocialLinks) > 0;
                                }
                            @endphp
                            @if($hasSocialLinks)
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 25px 0;">
                                <tr>
                                    <td align="center" style="padding-bottom: 20px;">
                                        <p style="margin: 0; font-size: 13px; color: #6b7280; padding-bottom: 15px; line-height: 1.4;">
                                            Follow us on social media:
                                        </p>
                                        <!-- Social Media Icons Table -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                                            <tr>
                                                @if(!empty($companyInfo['social']['facebook']) && trim($companyInfo['social']['facebook']) !== '' && $companyInfo['social']['facebook'] !== '#')
                                                <td style="padding: 0 6px;">
                                                    <a href="{{ $companyInfo['social']['facebook'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; width: 40px; height: 40px; background-color: #6c9bdc; border-radius: 50%; text-align: center; line-height: 40px; text-decoration: none;" class="social-icon">
                                                        <span style="color: #ffffff; font-size: 16px; font-weight: bold;">f</span>
                                                    </a>
                                                </td>
                                                @endif
                                                @if(!empty($companyInfo['social']['twitter']) && trim($companyInfo['social']['twitter']) !== '' && $companyInfo['social']['twitter'] !== '#')
                                                <td style="padding: 0 6px;">
                                                    <a href="{{ $companyInfo['social']['twitter'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; width: 40px; height: 40px; background-color: #6ab8f5; border-radius: 50%; text-align: center; line-height: 40px; text-decoration: none;" class="social-icon">
                                                        <span style="color: #ffffff; font-size: 14px; font-weight: bold;">X</span>
                                                    </a>
                                                </td>
                                                @endif
                                                @if(!empty($companyInfo['social']['linkedin']) && trim($companyInfo['social']['linkedin']) !== '' && $companyInfo['social']['linkedin'] !== '#')
                                                <td style="padding: 0 6px;">
                                                    <a href="{{ $companyInfo['social']['linkedin'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; width: 40px; height: 40px; background-color: #5aa0d0; border-radius: 50%; text-align: center; line-height: 40px; text-decoration: none;" class="social-icon">
                                                        <span style="color: #ffffff; font-size: 14px; font-weight: bold;">in</span>
                                                    </a>
                                                </td>
                                                @endif
                                                @if(!empty($companyInfo['social']['instagram']) && trim($companyInfo['social']['instagram']) !== '' && $companyInfo['social']['instagram'] !== '#')
                                                <td style="padding: 0 6px;">
                                                    <a href="{{ $companyInfo['social']['instagram'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; width: 40px; height: 40px; background-color: #e87994; border-radius: 50%; text-align: center; line-height: 40px; text-decoration: none;" class="social-icon">
                                                        <span style="color: #ffffff; font-size: 14px; font-weight: bold;">IG</span>
                                                    </a>
                                                </td>
                                                @endif
                                                @if(!empty($companyInfo['social']['youtube']) && trim($companyInfo['social']['youtube']) !== '' && $companyInfo['social']['youtube'] !== '#')
                                                <td style="padding: 0 6px;">
                                                    <a href="{{ $companyInfo['social']['youtube'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; width: 40px; height: 40px; background-color: #ff6666; border-radius: 50%; text-align: center; line-height: 40px; text-decoration: none;" class="social-icon">
                                                        <span style="color: #ffffff; font-size: 14px; font-weight: bold;">YT</span>
                                                    </a>
                                                </td>
                                                @endif
                                                @if(!empty($companyInfo['social']['whatsapp']) && trim($companyInfo['social']['whatsapp']) !== '' && $companyInfo['social']['whatsapp'] !== '#')
                                                <td style="padding: 0 6px;">
                                                    <a href="{{ $companyInfo['social']['whatsapp'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; width: 40px; height: 40px; background-color: #5fd975; border-radius: 50%; text-align: center; line-height: 40px; text-decoration: none;" class="social-icon">
                                                        <span style="color: #ffffff; font-size: 12px; font-weight: bold;">WA</span>
                                                    </a>
                                                </td>
                                                @endif
                                                @if(!empty($companyInfo['social']['telegram']) && trim($companyInfo['social']['telegram']) !== '' && $companyInfo['social']['telegram'] !== '#')
                                                <td style="padding: 0 6px;">
                                                    <a href="{{ $companyInfo['social']['telegram'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; width: 40px; height: 40px; background-color: #5ba9d9; border-radius: 50%; text-align: center; line-height: 40px; text-decoration: none;" class="social-icon">
                                                        <span style="color: #ffffff; font-size: 14px; font-weight: bold;">TG</span>
                                                    </a>
                                                </td>
                                                @endif
                                                @if(!empty($companyInfo['social']['pinterest']) && trim($companyInfo['social']['pinterest']) !== '' && $companyInfo['social']['pinterest'] !== '#')
                                                <td style="padding: 0 6px;">
                                                    <a href="{{ $companyInfo['social']['pinterest'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; width: 40px; height: 40px; background-color: #e05a6e; border-radius: 50%; text-align: center; line-height: 40px; text-decoration: none;" class="social-icon">
                                                        <span style="color: #ffffff; font-size: 14px; font-weight: bold;">P</span>
                                                    </a>
                                                </td>
                                                @endif
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Quick Links -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 20px 0;">
                                <tr>
                                    <td align="center" style="padding: 15px 0; border-top: 1px solid rgba(0, 0, 0, 0.08);">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="padding: 0 10px;">
                                                    <a href="{{ route('home') }}" style="color: #5b9bd5; text-decoration: none; font-size: 13px; line-height: 1.5;">Home</a>
                                                </td>
                                                <td style="padding: 0 10px; border-left: 1px solid rgba(0, 0, 0, 0.1);">
                                                    <a href="{{ route('about') }}" style="color: #5b9bd5; text-decoration: none; font-size: 13px; line-height: 1.5;">About Us</a>
                                                </td>
                                                <td style="padding: 0 10px; border-left: 1px solid rgba(0, 0, 0, 0.1);">
                                                    <a href="{{ route('contact') }}" style="color: #5b9bd5; text-decoration: none; font-size: 13px; line-height: 1.5;">Contact</a>
                                                </td>
                                                @if(!empty($companyInfo) && !empty($companyInfo['website']))
                                                <td style="padding: 0 10px; border-left: 1px solid rgba(0, 0, 0, 0.1);">
                                                    <a href="{{ $companyInfo['website'] }}" target="_blank" rel="noopener noreferrer" style="color: #5b9bd5; text-decoration: none; font-size: 13px; line-height: 1.5;">Website</a>
                                                </td>
                                                @endif
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Business Hours -->
                            @if(!empty($companyInfo) && !empty($companyInfo['business_hours']) && is_array($companyInfo['business_hours']))
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 15px 0; border-top: 1px solid rgba(0, 0, 0, 0.08);">
                                        <p style="margin: 0; font-size: 12px; color: #6b7280; line-height: 1.7;">
                                            <strong style="color: #4a5568; font-weight: 600;">Business Hours:</strong><br>
                                            @if(!empty($companyInfo['business_hours']['weekdays']))
                                                {{ $companyInfo['business_hours']['weekdays'] }}<br>
                                            @endif
                                            @if(!empty($companyInfo['business_hours']['weekend']))
                                                {{ $companyInfo['business_hours']['weekend'] }}<br>
                                            @endif
                                            @if(!empty($companyInfo['business_hours']['sunday']))
                                                {{ $companyInfo['business_hours']['sunday'] }}
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Copyright -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-top: 25px; border-top: 1px solid rgba(0, 0, 0, 0.08);">
                                        <p style="margin: 0; font-size: 12px; color: #6b7280; line-height: 1.5;">
                                            &copy; {{ date('Y') }} SuGanta Tutors. All rights reserved.
                                        </p>
                                        <p style="margin: 10px 0 0 0; font-size: 11px; color: #9ca3af; line-height: 1.5;">
                                            This email was sent to {{ (!empty($user) && !empty($user->email)) ? $user->email : 'you' }}. If you believe this was sent in error, please contact us.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <!--[if mso]>
                </td>
                </tr>
                </table>
                <![endif]-->

                <!-- Spacer -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 20px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #b0b0b0; line-height: 1.5;">
                                You're receiving this email because you're registered with SuGanta Tutors.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
