<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_participants', function (Blueprint $table) {
            $table->string('code', 16)->nullable()->after('order_product_item_id');
        });

        DB::table('order_participants')->whereNull('code')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                do {
                    $code = Str::lower(Str::random(9));
                } while (DB::table('order_participants')->where('code', $code)->exists());

                DB::table('order_participants')->where('id', $row->id)->update(['code' => $code]);
            }
        });

        Schema::table('order_participants', function (Blueprint $table) {
            $table->string('code', 16)->nullable(false)->change();
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('order_participants', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
