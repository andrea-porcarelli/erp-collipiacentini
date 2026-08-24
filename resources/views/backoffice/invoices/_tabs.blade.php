@props(['active' => 'issued'])
<div class="d-flex gap-2 mb-spacing-xl">
    <a href="{{ route('invoices.index') }}" class="text-decoration-none">
        <x-chip label="Fatture emesse" :appearance="$active === 'issued' ? 'Active' : 'Resting'" />
    </a>
    <a href="{{ route('invoices.pending') }}" class="text-decoration-none">
        <x-chip label="Ordini da fatturare" :appearance="$active === 'pending' ? 'Active' : 'Resting'" />
    </a>
</div>
