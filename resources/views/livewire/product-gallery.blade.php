<div class="product-gallery-container">
    @if (count($images) > 0)
        <!-- Immagine principale -->
        <div class="gallery-main-image">
            @if ($selectedImage)
                <img src="{{ asset('storage/' . $selectedImage->file_path) }}"
                     alt="{{ $selectedImage->description ?? 'Gallery image' }}"
                     class="main-image"
                     onerror="this.onerror=null; this.src='https://via.placeholder.com/800x600/2A3493/ffffff?text=Immagine+Non+Disponibile';">
            @endif
        </div>

        <!-- Thumbnails scorrevoli -->
        @if (count($images) > 1)
            <div class="gallery-thumbnails-wrapper">
                <div class="gallery-thumbnails" id="gallery-thumbnails-{{ $product->id }}">
                    @foreach ($images as $index => $image)
                        <div class="thumbnail-item {{ $selectedImage && $selectedImage->id === $image->id ? 'active' : '' }}"
                             wire:click="selectImage({{ $index }})"
                             data-index="{{ $index }}">
                            <img src="{{ asset('storage/' . $image->file_path) }}"
                                 alt="{{ $image->description ?? 'Thumbnail ' . ($index + 1) }}"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/100x100/2A3493/ffffff?text={{ $index + 1 }}';">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Contatore immagini -->
        <div class="gallery-counter">
            <span class="current">{{ array_search($selectedImage, $images) + 1 }}</span>
            <span class="separator">/</span>
            <span class="total">{{ count($images) }}</span>
        </div>
    @else
        <div class="no-images">
            <i class="fa-regular fa-image"></i>
            <p>Nessuna immagine disponibile</p>
        </div>
    @endif
</div>

