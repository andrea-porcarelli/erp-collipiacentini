<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LanguageContent extends LogsModel
{
    public $fillable = [
        'language_id',
        'entity_id',
        'entity_type',
        'field',
        'value',
    ];

    public function language() : BelongsTo {
        return $this->belongsTo(Language::class);
    }

    public function entity() : MorphTo {
        return $this->morphTo();
    }
}
