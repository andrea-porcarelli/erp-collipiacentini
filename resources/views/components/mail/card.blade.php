@props([
    'status' => 'success',
    'title' => '',
])

@php
    $t = config('design.tokens');
    $variants = [
        'success' => ['color' => $t['brand-success-brand'], 'icon' => '&#10003;'],
        'warning' => ['color' => $t['brand-warning-brand'], 'icon' => '!'],
        'danger'  => ['color' => $t['brand-error-brand'],   'icon' => '&#10005;'],
    ];
    $variant = $variants[$status] ?? $variants['success'];
    $bgCard = $t['background-global-paper1'];
    $bgCardHead = $t['background-global-paper2'];
    $borderLight = $t['border-light'];
    $textMain = $t['text-main'];
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:{{ $bgCard }}; border-radius:14px; border:1px solid {{ $borderLight }};">
    @if($title)
        <tr>
            <td align="center" style="padding:28px 24px 24px 24px; border-bottom:1px solid {{ $borderLight }}; background-color:{{ $bgCardHead }}; border-radius:14px 14px 0 0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                    <tr>
                        <td style="padding-right:10px; vertical-align:middle;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" valign="middle" width="24" height="24" style="background-color:{{ $variant['color'] }}; border-radius:12px; color:#FFFFFF; font-size:14px; font-weight:700; line-height:24px; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                        {!! $variant['icon'] !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="vertical-align:middle; font-size:20px; font-weight:700; color:{{ $variant['color'] }}; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                            {{ $title }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    @endif
    <tr>
        <td style="padding:24px; font-size:15px; line-height:1.6; color:{{ $textMain }}; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
            {{ $slot }}
        </td>
    </tr>
</table>
