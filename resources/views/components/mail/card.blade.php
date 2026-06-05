@props([
    'status' => 'success',
    'title' => '',
])

@php
    $variants = [
        'success' => ['color' => '#4F46E5', 'icon' => '&#10003;'],
        'warning' => ['color' => '#F59E0B', 'icon' => '!'],
        'danger'  => ['color' => '#DC2626', 'icon' => '&#10005;'],
    ];
    $variant = $variants[$status] ?? $variants['success'];
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFFFFF; border-radius:14px; border:1px solid #E5E7EB;">
    @if($title)
        <tr>
            <td align="center" style="padding:28px 24px 24px 24px; border-bottom:1px solid #E5E7EB; background-color:#FAFAFA; border-radius:14px 14px 0 0;">
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
        <td style="padding:24px; font-size:15px; line-height:1.6; color:#111827; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
            {{ $slot }}
        </td>
    </tr>
</table>
