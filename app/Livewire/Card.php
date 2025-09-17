<?php

namespace App\Livewire;

use Illuminate\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use Livewire\Component;

class Card extends Component
{
    public string $title;
    public string $sub_title;
    public string $blade;
    public string $body;
    public bool $title_center = false;

    public function mount(): void
    {
        // Usa mount invece di boot per l'inizializzazione
        if (!empty($this->blade)) {
            try {
                // Verifica che la vista esista
                if (ViewFacade::exists($this->blade)) {
                    $this->body = view($this->blade)->render();
                } else {
                    $this->body = '<div class="alert alert-warning">Vista non trovata: ' . $this->blade . '</div>';
                }
            } catch (\Exception $e) {
                $this->body = '<div class="alert alert-danger">Errore nel render della vista: ' . $e->getMessage() . '</div>';
            }
        }
    }
    public function render() : View
    {
        return view('livewire.card');
    }
}
