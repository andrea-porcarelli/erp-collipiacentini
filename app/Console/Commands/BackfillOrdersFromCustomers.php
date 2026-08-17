<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillOrdersFromCustomers extends Command
{
    protected $signature = 'customers:backfill-orders {--dry-run : Non applica modifiche, stampa solo il report}';

    protected $description = 'Copia dati anagrafici da customers a orders (denormalizzazione) e da customer_consents a order_consents. Idempotente.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY-RUN attivo: nessuna scrittura sarà eseguita.');
        }

        $ordersWithCustomer = DB::table('orders')->whereNotNull('customer_id')->count();
        $ordersOrphan = DB::table('orders')->whereNull('customer_id')->count();

        $this->line("Ordini con customer_id: {$ordersWithCustomer}");
        $this->line("Ordini orfani (customer_id NULL): {$ordersOrphan}");

        if ($ordersOrphan > 0) {
            $this->warn("Attenzione: {$ordersOrphan} ordini senza customer_id andranno gestiti manualmente.");
        }

        $anagraficiCopied = 0;
        $consentsCopied = 0;

        DB::beginTransaction();
        try {
            DB::table('orders as o')
                ->join('customers as c', 'c.id', '=', 'o.customer_id')
                ->whereNotNull('o.customer_id')
                ->whereNull('o.email')
                ->select(
                    'o.id as order_id',
                    'o.partner_id as order_partner_id',
                    'c.id as customer_id',
                    'c.name',
                    'c.surname',
                    'c.email',
                    'c.phone',
                    'c.prefix_phone',
                    'c.address',
                    'c.city',
                    'c.zip_code',
                    'c.country_id',
                    'c.fiscal_code',
                    'c.birth_date',
                    'c.privacy_accepted',
                    'c.newsletter'
                )
                ->orderBy('o.id')
                ->chunkById(500, function ($rows) use (&$anagraficiCopied, $dryRun) {
                    foreach ($rows as $row) {
                        $payload = [
                            'name' => $row->name,
                            'surname' => $row->surname,
                            'email' => $row->email,
                            'phone' => $row->phone,
                            'prefix_phone' => $row->prefix_phone,
                            'address' => $row->address,
                            'city' => $row->city,
                            'zip_code' => $row->zip_code,
                            'country_id' => $row->country_id,
                            'fiscal_code' => $row->fiscal_code,
                            'birth_date' => $row->birth_date,
                            'privacy_accepted' => (int) $row->privacy_accepted,
                            'newsletter' => (int) $row->newsletter,
                        ];

                        if (! $dryRun) {
                            DB::table('orders')->where('id', $row->order_id)->update($payload);
                        }
                        $anagraficiCopied++;
                    }
                }, 'o.id', 'order_id');

            $now = now();

            DB::table('orders as o')
                ->join('customer_consents as cc', function ($join) {
                    $join->on('cc.customer_id', '=', 'o.customer_id')
                        ->on('cc.partner_id', '=', 'o.partner_id');
                })
                ->whereNotNull('o.customer_id')
                ->leftJoin('order_consents as oc', function ($join) {
                    $join->on('oc.order_id', '=', 'o.id')
                        ->on('oc.partner_consent_id', '=', 'cc.partner_consent_id');
                })
                ->whereNull('oc.id')
                ->select(
                    'o.id as order_id',
                    'cc.partner_consent_id',
                    'cc.partner_id',
                    'cc.accepted',
                    'cc.subscribed_at',
                    'cc.expires_at'
                )
                ->orderBy('o.id')
                ->chunkById(500, function ($rows) use (&$consentsCopied, $dryRun, $now) {
                    $batch = [];
                    foreach ($rows as $row) {
                        $batch[] = [
                            'order_id' => $row->order_id,
                            'partner_consent_id' => $row->partner_consent_id,
                            'partner_id' => $row->partner_id,
                            'accepted' => (int) $row->accepted,
                            'subscribed_at' => $row->subscribed_at ?? $now,
                            'expires_at' => $row->expires_at,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $consentsCopied++;
                    }
                    if (! $dryRun && ! empty($batch)) {
                        DB::table('order_consents')->insert($batch);
                    }
                }, 'o.id', 'order_id');

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Backfill fallito: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Ordini con anagrafica copiata: '.$anagraficiCopied);
        $this->info('Consensi copiati in order_consents: '.$consentsCopied);
        $this->info('Backfill completato.');

        return self::SUCCESS;
    }
}
