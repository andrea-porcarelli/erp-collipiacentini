@props([
    'label' => '',
    'value' => '',
])

<td width="50%" valign="top" style="padding:12px 12px 12px 0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <div style="font-size:11px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; color:#9CA3AF; padding-bottom:4px;">
        {{ $label }}
    </div>
    <div style="font-size:16px; font-weight:700; color:#111827; line-height:1.4;">
        {{ $value !== '' ? $value : $slot }}
    </div>
</td>
