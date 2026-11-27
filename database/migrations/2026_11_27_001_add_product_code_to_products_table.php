<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_code', 64)->nullable()->unique()->after('label');
        });

        $rows = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('partners', 'products.partner_id', '=', 'partners.id')
            ->whereNotNull('categories.category_code')
            ->whereNotNull('partners.partner_code')
            ->select('products.id', 'categories.category_code', 'partners.partner_code')
            ->get();

        foreach ($rows as $row) {
            DB::table('products')->where('id', $row->id)->update([
                'product_code' => sprintf(
                    '%s-%s%s',
                    $row->category_code,
                    $row->partner_code,
                    str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)
                ),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['product_code']);
            $table->dropColumn('product_code');
        });
    }
};
