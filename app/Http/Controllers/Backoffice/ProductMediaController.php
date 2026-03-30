<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductMediaController extends Controller
{
    private function authorizeAccess(Product $product): void
    {
        $user = Auth::user();
        if (in_array($user->role, ['god', 'admin'])) {
            return;
        }
        if ($user->role === 'partner' && $product->partner_id !== $user->partner_id) {
            abort(403);
        }
    }

    /**
     * Carica una nuova immagine nella gallery del prodotto.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAccess($product);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($product->gallery()->count() >= 5) {
            return response()->json(['message' => 'Hai raggiunto il limite massimo di 5 immagini.'], 422);
        }

        $file     = $request->file('image');
        $path     = $file->store("products/{$product->id}", 'public');
        $maxOrder = $product->gallery()->max('sort_order') ?? -1;

        $media = Media::create([
            'mediable_type' => Product::class,
            'mediable_id'   => $product->id,
            'media_type'    => 'gallery',
            'file_name'     => $file->getClientOriginalName(),
            'file_path'     => $path,
            'file_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),
            'sort_order'    => $maxOrder + 1,
        ]);

        return response()->json([
            'id'        => $media->id,
            'file_name' => $media->file_name,
            'url'       => asset('storage/' . $media->file_path),
        ]);
    }

    /**
     * Aggiorna l'ordine delle immagini della gallery.
     */
    public function reorder(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAccess($product);

        $request->validate([
            'ordered_ids'   => 'required|array',
            'ordered_ids.*' => 'integer',
        ]);

        foreach ($request->input('ordered_ids') as $index => $id) {
            Media::where('id', $id)
                ->where('mediable_type', Product::class)
                ->where('mediable_id', $product->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Elimina un'immagine dalla gallery.
     */
    public function destroy(Product $product, Media $media): JsonResponse
    {
        abort_if($media->mediable_id !== $product->id || $media->mediable_type !== Product::class, 403);
        $this->authorizeAccess($product);

        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Restituisce la descrizione lunga del prodotto per una lingua specifica.
     */
    public function getLongDescription(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAccess($product);

        $lang  = Language::findOrFail($request->validate(['language_id' => 'required|integer|exists:languages,id'])['language_id']);
        $value = $product->contentField('long_description', $lang->iso_code) ?? '';

        return response()->json(['long_description' => $value]);
    }
}
