@props([
    'title' => null,
    'partnerName' => null,
    'supportEmail' => null,
    'preheader' => null,
])

@php
    $title ??= config('app.name');
    $supportEmail ??= config('mail.support_address');
    $signature = $partnerName ?: 'Lo staff di miticko';

    $logoPath = public_path('assets/images/logo-miticko.png');
    $logoSrc = is_file($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : asset('assets/images/logo-miticko.png');

    $brandOrange = '#E55E1D';
@endphp

<!DOCTYPE html>
<html lang="it" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $title }}</title>
</head>
<body style="margin:0; padding:0; background-color:#F3F4F6; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#111827;">
    @if($preheader)
        <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all; font-size:1px; line-height:1px; color:#F3F4F6;">{{ $preheader }}</div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F3F4F6;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px;">

                    <tr>
                        <td align="center" style="padding:0 0 28px 0;">
                            <img src="{{ $logoSrc }}" alt="miticko" width="140" style="display:block; border:0; outline:none; text-decoration:none; height:auto; max-width:140px;">
                        </td>
                    </tr>

                    <tr>
                        <td>
                            {{ $slot }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 8px 8px 8px; font-size:14px; line-height:1.5; color:#6B7280; text-align:left;">
                            Per modifiche o assistenza scrivi a <a href="mailto:{{ $supportEmail }}" style="color:{{ $brandOrange }}; text-decoration:underline;">{{ $supportEmail }}</a>.
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 8px 0 8px; font-size:14px; line-height:1.6; color:#111827;">
                            A presto,<br>
                            {{ $signature }}
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
