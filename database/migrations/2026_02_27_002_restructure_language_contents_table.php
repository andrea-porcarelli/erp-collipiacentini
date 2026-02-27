<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_contents', function (Blueprint $table) {
            $columns = ['intro', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
            $toDrop = array_filter($columns, fn($col) => Schema::hasColumn('language_contents', $col));
            if (!empty($toDrop)) {
                $table->dropColumn(array_values($toDrop));
            }
        });

        Schema::table('language_contents', function (Blueprint $table) {
            $table->string('entity_type', 255)->change();
            $table->string('field')->after('entity_type');
            $table->longText('value')->nullable()->after('field');
            $table->unique(['language_id', 'entity_id', 'entity_type', 'field'], 'language_contents_unique');
        });
    }

    public function down(): void
    {
        Schema::table('language_contents', function (Blueprint $table) {
            $table->dropUnique('language_contents_unique');
            $table->dropColumn(['field', 'value']);
            $table->text('entity_type')->change();
        });

        Schema::table('language_contents', function (Blueprint $table) {
            $table->text('intro')->nullable();
            $table->text('description')->nullable();
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
        });
    }
};
