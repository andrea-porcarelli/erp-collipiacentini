<?php

namespace App\Models;

use App\Traits\HasLanguageContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductFeature extends Model
{
    use HasLanguageContent;

    public const CATEGORIES = [
        'accessibility' => 'Accessibilità',
        'pets'          => 'Animali',
        'services'      => 'Servizi disponibili',
        'suitability'   => 'Adatto per',
    ];

    public $fillable = [
        'category',
        'code',
        'label',
        'icon',
        'sort_order',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function getTranslatedLabelAttribute(): string
    {
        return $this->contentField('label') ?? $this->label;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
