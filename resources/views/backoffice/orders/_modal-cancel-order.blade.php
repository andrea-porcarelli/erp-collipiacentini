@php($canRefund = ! empty($order->stripe_payment_intent_id))
<x-modal id="modal-cancel-order" title="Annulla ordine" primary="Conferma annullamento" secondary="Annulla">
    <form id="form-cancel-order">
        <p class="mb-3">
            Questa azione annulla l'ordine <strong>#{{ $order->order_number }}</strong>,
            libera i posti prenotati e invia un'email di notifica al cliente.
        </p>
        <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="issue_refund" id="cancel-refund-yes" value="1"
                @disabled(! $canRefund)
                @checked($canRefund)>
            <label class="form-check-label" for="cancel-refund-yes">
                Emetti rimborso integrale ({{ number_format($order->amount, 2, ',', '.') }} €)
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="issue_refund" id="cancel-refund-no" value="0"
                @checked(! $canRefund)>
            <label class="form-check-label" for="cancel-refund-no">
                Non emettere rimborso
            </label>
        </div>
        @if(! $canRefund)
            <small class="text-secondary d-block mt-2">
                Questo ordine non ha un pagamento Stripe associato, quindi non è possibile emettere un rimborso.
            </small>
        @endif
    </form>
</x-modal>
