@props([
    'title' => '',
    'datetime' => '',
])

@php($t = config('design.brands.'.config('design.default_brand', 'miticko').'.tokens', []))

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 4px 0;">
    <tr>
        <td style="font-size:20px; font-weight:700; color:{{ $t['text-main'] }}; line-height:1.3; padding:0 0 4px 0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
            {{ $title }}
        </td>
    </tr>
    @if($datetime)
        <tr>
            <td style="font-size:15px; font-weight:600; color:{{ $t['text-main'] }}; line-height:1.4; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                {{ $datetime }}
            </td>
        </tr>
    @endif
</table>
