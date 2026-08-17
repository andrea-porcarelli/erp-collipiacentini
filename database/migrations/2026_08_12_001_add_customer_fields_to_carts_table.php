<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->string('name')->nullable()->after('customer_id');
            $table->string('surname')->nullable()->after('name');
            $table->string('email')->nullable()->after('surname');
            $table->string('prefix_phone')->nullable()->after('email');
            $table->string('phone')->nullable()->after('prefix_phone');
            $table->string('address')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
            $table->string('zip_code')->nullable()->after('city');
            $table->string('country_id')->nullable()->after('zip_code');
            $table->string('fiscal_code', 32)->nullable()->after('country_id');
            $table->date('birth_date')->nullable()->after('fiscal_code');
            $table->boolean('privacy_accepted')->default(false)->after('birth_date');
            $table->boolean('newsletter')->default(false)->after('privacy_accepted');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'surname',
                'email',
                'prefix_phone',
                'phone',
                'address',
                'city',
                'zip_code',
                'country_id',
                'fiscal_code',
                'birth_date',
                'privacy_accepted',
                'newsletter',
            ]);
        });
    }
};
