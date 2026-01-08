<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;

class ProductGallery extends Component
{
    public Product $product;
    public $selectedImage = null;
    public $images = [];

    public function mount(Product $product)
    {
        $this->product = $product;
        $this->loadImages();

        // Seleziona la prima immagine come default
        if (count($this->images) > 0) {
            $this->selectedImage = $this->images[0];
        }
    }

    public function loadImages()
    {
        // Carica prima le immagini della galleria del prodotto
        $productGallery = $this->product->gallery()->get();

        // Poi carica le immagini della galleria del partner
        $partnerGallery = $this->product->partner->gallery()->get();

        // Combina le collezioni
        $this->images = $productGallery->merge($partnerGallery)->all();
    }

    public function selectImage($index)
    {
        if (isset($this->images[$index])) {
            $this->selectedImage = $this->images[$index];
        }
    }

    public function render()
    {
        return view('livewire.product-gallery');
    }
}
