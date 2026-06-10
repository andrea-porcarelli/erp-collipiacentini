@props([
    'title' => null,
    'partnerName' => null,
    'supportEmail' => null,
    'preheader' => null,
    'brand' => null,
    'partnerLogoPath' => null,
    'partnerLogoUrl' => null,
])

@php
    $title ??= config('app.name');
    $supportEmail ??= config('mail.support_address');

    $defaultBrand = config('design.default_brand', 'miticko');
    $brand = $brand ?: $defaultBrand;

    $signature = $partnerName ?: 'Lo staff di '.$brand;

    $logoPath = null;
    $logoFallback = null;

    if ($partnerLogoPath && file_exists($partnerLogoPath)) {
        $logoPath = $partnerLogoPath;
        $logoFallback = $partnerLogoUrl ?: asset("assets/images/logo-{$defaultBrand}.png");
    } else {
        $logoCandidate = public_path("assets/images/logo-{$brand}.png");
        $logoPath = file_exists($logoCandidate) ? $logoCandidate : public_path("assets/images/logo-{$defaultBrand}.png");
        $logoFallback = asset('assets/images/logo-'.(file_exists($logoCandidate) ? $brand : $defaultBrand).'.png');
    }

    $logoSrc = isset($message) ? $message->embed($logoPath) : $logoFallback;

    $t = config("design.brands.{$brand}.tokens") ?? config("design.brands.{$defaultBrand}.tokens", []);
    $brandOrange = $t['brand-primary-brand'];
    $textMain = $t['text-main'];
    $textSecondary = $t['text-secondary'];
    $bgPage = $t['background-global-default'];
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
<body style="margin:0; padding:0; background-color:{{ $bgPage }}; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:{{ $textMain }};">
    @if($preheader)
        <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all; font-size:1px; line-height:1px; color:{{ $bgPage }};">{{ $preheader }}</div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:{{ $bgPage }};">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px;">

                    <tr>
                        <td align="center" style="padding:0 0 28px 0;">
                            <img src="{{ $logoSrc }}" alt="{{ $brand }}" width="140" style="display:block; border:0; outline:none; text-decoration:none; height:auto; max-width:140px;">
                        </td>
                    </tr>

                    <tr>
                        <td>
                            {{ $slot }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 8px 8px 8px; font-size:14px; line-height:1.5; color:{{ $textSecondary }}; text-align:left;">
                            Per modifiche o assistenza scrivi a <a href="mailto:{{ $supportEmail }}" style="color:{{ $brandOrange }}; text-decoration:underline;">{{ $supportEmail }}</a>.
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 8px 0 8px; font-size:14px; line-height:1.6; color:{{ $textMain }};">
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
