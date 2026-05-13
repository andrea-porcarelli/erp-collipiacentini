<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('order_product_items as opi')
            ->join('order_products as op', 'op.id', '=', 'opi.order_product_id')
            ->select('opi.id as opi_id', 'op.order_id', 'opi.quantity')
            ->orderBy('opi.id')
            ->chunkById(500, function ($rows) use ($now) {
                $rowsToInsert = [];
                foreach ($rows as $row) {
                    $existing = DB::table('order_participants')
                        ->where('order_product_item_id', $row->opi_id)
                        ->count();

                    $missing = max(0, ((int) $row->quantity) - $existing);
                    for ($i = 0; $i < $missing; $i++) {
                        $rowsToInsert[] = [
                            'order_id'              => $row->order_id,
                            'order_product_item_id' => $row->opi_id,
                            'status'                => 'booked',
                            'created_at'            => $now,
                            'updated_at'            => $now,
                        ];
                    }
                }
                if (!empty($rowsToInsert)) {
                    DB::table('order_participants')->insert($rowsToInsert);
                }
            }, 'opi.id', 'opi_id');
    }

    public function down(): void
    {
        // No-op: i dati di backfill non vengono rimossi automaticamente.
    }
};
