<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Mail\Mailables\Content;

class Language extends LogsModel
{
    public $fillable = [
        'is_active',
        'label',
        'iso_code',
    ];

    public function contents() : HasMany {
        return $this->hasMany(LanguageContent::class);
    }

}
