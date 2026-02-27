<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerFieldTypeSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            ['key' => 'address',    'label' => 'Indirizzo di residenza', 'sort_order' => 1],
            ['key' => 'birth_date', 'label' => 'Data di nascita',        'sort_order' => 2],
            ['key' => 'phone',      'label' => 'Cellulare',              'sort_order' => 3],
            ['key' => 'tax_code',   'label' => 'Codice fiscale',         'sort_order' => 4],
        ];

        foreach ($fields as $field) {
            DB::table('customer_field_types')->updateOrInsert(
                ['key' => $field['key']],
                array_merge($field, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
