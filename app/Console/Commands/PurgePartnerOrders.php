<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Partner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgePartnerOrders extends Command
{
    protected $signature = 'orders:purge-partner
        {partner_id : ID del partner di cui eliminare ordini e carrelli}
        {--include-carts : Elimina anche i carrelli aperti per il partner}
        {--dry-run : Mostra solo cosa verrebbe eliminato, senza toccare il DB}
        {--force : Salta la conferma interattiva}';

    protected $description = 'Elimina tutti gli ordini (con prodotti, biglietti, log, consensi) di un partner.';

    public function handle(): int
    {
        $partnerId = (int) $this->argument('partner_id');
        $includeCarts = (bool) $this->option('include-carts');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $partner = Partner::find($partnerId);
        if (! $partner) {
            $this->error("Partner #{$partnerId} non trovato.");

            return self::FAILURE;
        }

        $orders = Order::where('partner_id', $partnerId);
        $orderIds = $orders->pluck('id');

        $stats = [
            'orders'             => $orderIds->count(),
            'order_products'     => OrderProduct::whereIn('order_id', $orderIds)->count(),
            'order_product_items' => DB::table('order_product_items')
                ->whereIn('order_product_id', OrderProduct::whereIn('order_id', $orderIds)->pluck('id'))
                ->count(),
            'participants'       => DB::table('order_participants')->whereIn('order_id', $orderIds)->count(),
            'order_logs'         => DB::table('order_logs')->whereIn('order_id', $orderIds)->count(),
            'customer_consents'  => DB::table('customer_consents')->whereIn('order_id', $orderIds)->count(),
            'amount_total'       => (float) Order::where('partner_id', $partnerId)->sum('amount'),
        ];

        if ($includeCarts) {
            $stats['carts'] = Cart::where('partner_id', $partnerId)->count();
            $stats['cart_items'] = DB::table('cart_items')
                ->whereIn('cart_id', Cart::where('partner_id', $partnerId)->pluck('id'))
                ->count();
        }

        $this->info("Partner: #{$partner->id} — {$partner->partner_name}");
        $this->line('Elementi da eliminare:');
        $this->table(
            ['Entità', 'Conteggio'],
            collect($stats)->map(fn ($v, $k) => [
                $k,
                $k === 'amount_total'
                    ? number_format($v, 2, ',', '.') . ' €'
                    : number_format($v, 0, ',', '.'),
            ])->values()->all(),
        );

        if ($stats['orders'] === 0 && (! $includeCarts || ($stats['carts'] ?? 0) === 0)) {
            $this->warn('Nulla da eliminare.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->comment('Dry-run: nessuna modifica al database.');

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("Confermi l'eliminazione DEFINITIVA dei dati elencati sopra?", false)) {
            $this->comment('Operazione annullata.');

            return self::SUCCESS;
        }

        $deleted = ['orders' => 0, 'order_products' => 0, 'carts' => 0];

        DB::transaction(function () use ($partnerId, $includeCarts, &$deleted) {
            // OrderProduct non ha FK cascade da orders.order_id (è un integer
            // semplice nella migration originale), quindi va eliminato a mano.
            // Eliminandolo, order_product_items cade in cascata (FK cascade).
            $opIds = OrderProduct::whereIn('order_id',
                Order::where('partner_id', $partnerId)->pluck('id')
            )->pluck('id');
            $deleted['order_products'] = OrderProduct::whereIn('id', $opIds)->delete();

            // Order delete fa cascade su participants, logs, customer_consents.
            $deleted['orders'] = Order::where('partner_id', $partnerId)->delete();

            if ($includeCarts) {
                // CartItem cascade su cart_id (verificato in migration carts).
                $deleted['carts'] = Cart::where('partner_id', $partnerId)->delete();
            }
        });

        $this->info("Eliminati: {$deleted['orders']} ordini, {$deleted['order_products']} order_products"
            . ($includeCarts ? ", {$deleted['carts']} carrelli" : '') . '.');

        return self::SUCCESS;
    }
}
