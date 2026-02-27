<?php

namespace App\Traits;

use App\Models\Language;
use App\Models\LanguageContent;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasLanguageContent
{
    private array $contentCache = [];

    public function contents(): MorphMany
    {
        return $this->morphMany(LanguageContent::class, 'entity');
    }

    /**
     * Restituisce una Collection { field => value } per la locale indicata.
     * Il risultato viene cachato per evitare query ripetute nello stesso ciclo.
     */
    public function content(?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();

        if (!isset($this->contentCache[$locale])) {
            $this->contentCache[$locale] = $this->contents()
                ->whereHas('language', fn($q) => $q->where('iso_code', $locale))
                ->pluck('value', 'field');
        }

        return $this->contentCache[$locale];
    }

    /**
     * Restituisce il valore di un singolo campo tradotto.
     */
    public function contentField(string $field, ?string $locale = null): ?string
    {
        return $this->content($locale)->get($field);
    }

    /**
     * Salva un singolo campo tradotto per la locale indicata.
     */
    public function setContentField(string $field, string $value, ?string $locale = null): void
    {
        $locale = $locale ?? app()->getLocale();
        $language = Language::where('iso_code', $locale)->firstOrFail();

        $this->contents()->updateOrCreate(
            ['language_id' => $language->id, 'field' => $field],
            ['value' => $value]
        );

        unset($this->contentCache[$locale]);
    }

    /**
     * Salva più campi tradotti in una sola chiamata.
     *
     * @param array<string, string> $fields  [ 'field' => 'value', ... ]
     */
    public function setContentFields(array $fields, ?string $locale = null): void
    {
        $locale = $locale ?? app()->getLocale();
        $language = Language::where('iso_code', $locale)->firstOrFail();

        foreach ($fields as $field => $value) {
            $this->contents()->updateOrCreate(
                ['language_id' => $language->id, 'field' => $field],
                ['value' => $value]
            );
        }

        unset($this->contentCache[$locale]);
    }
}
