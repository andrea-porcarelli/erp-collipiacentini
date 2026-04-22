<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_features', function (Blueprint $table) {
            $table->id();
            $table->string('category', 32)->index();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now  = now();
        $rows = [];
        $seed = [
            'accessibility' => [
                ['wheelchair_accessible', 'Accessibile a persone in sedia a rotelle', 'fa-wheelchair'],
                ['stroller_accessible',   'Accessibile con passeggino',               'fa-baby-carriage'],
                ['accessible_toilets',    'Bagni accessibili',                        'fa-restroom'],
                ['elevator',              'Ascensore disponibile',                    'fa-elevator'],
                ['stairs',                'Presenza di scale',                        'fa-stairs'],
            ],
            'pets' => [
                ['pets_allowed',      'Animali ammessi',                      'fa-paw'],
                ['small_pets_allowed','Animali di piccola taglia ammessi',    'fa-cat'],
                ['pets_outdoor_only', 'Animali ammessi solo in aree esterne', 'fa-tree'],
                ['pets_not_allowed',  'Animali non ammessi',                  'fa-ban'],
                ['dog_sitter',        'Dog sitter',                           'fa-dog'],
            ],
            'services' => [
                ['audio_guide',    'Audioguida disponibile', 'fa-headphones'],
                ['free_parking',   'Parcheggio gratuito',    'fa-square-parking'],
                ['paid_parking',   'Parcheggio a pagamento', 'fa-square-parking'],
                ['public_parking', 'Parcheggio pubblico',    'fa-square-parking'],
                ['cafeteria',      'Caffetteria',            'fa-mug-hot'],
                ['restaurant',     'Ristorante',             'fa-utensils'],
                ['gift_shop',      'Gift shop',              'fa-gift'],
                ['cloakroom',      'Guardaroba',             'fa-coat-hanger'],
            ],
            'suitability' => [
                ['family',   'Adatto a famiglie',      'fa-people-roof'],
                ['group',    'Adatto a gruppi',        'fa-users'],
                ['school',   'Adatto a scuole',        'fa-school'],
                ['couples',  'Adatto a coppie',        'fa-heart'],
                ['outdoor',  'Esperienza all\'aperto', 'fa-sun'],
            ],
        ];

        foreach ($seed as $category => $items) {
            foreach ($items as $i => [$code, $label, $icon]) {
                $rows[] = [
                    'category'   => $category,
                    'code'       => $code,
                    'label'      => $label,
                    'icon'       => $icon,
                    'sort_order' => $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('product_features')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_features');
    }
};
