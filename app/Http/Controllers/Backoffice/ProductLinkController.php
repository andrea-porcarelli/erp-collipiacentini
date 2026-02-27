<?php

namespace App\Http\Controllers\Backoffice;

use App\Interfaces\ProductLinkInterface;
use App\Interfaces\ProductInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductLinkController extends CrudController
{
    public ProductLinkInterface $interface;
    public string $path = 'product-links';

    public function __construct(ProductLinkInterface $interface)
    {
        $this->interface = $interface;
    }

    public function index(int $productId): JsonResponse
    {
        try {
            $links = $this->interface->filters(['product_id' => $productId])
                ->with('language')
                ->get()
                ->map(fn($link) => [
                    'id'          => $link->id,
                    'language_id' => $link->language_id,
                    'language'    => $link->language?->label,
                    'label'       => $link->label,
                    'link'        => $link->link,
                ]);

            return $this->success(['data' => $links]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        try {
            $request->validate([
                'label' => 'required|string|max:255',
                'link'  => 'required|string|max:255',
            ]);

            $link = $this->interface->store([
                'product_id'  => $productId,
                'language_id' => $request->input('language_id') ?: null,
                'label'       => $request->input('label'),
                'link'        => $request->input('link'),
            ]);

            $link->load('language');

            return $this->success([
                'id'          => $link->id,
                'language_id' => $link->language_id,
                'language'    => $link->language?->label,
                'label'       => $link->label,
                'link'        => $link->link,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function update(Request $request, int $productId, int $linkId): JsonResponse
    {
        try {
            $request->validate([
                'label' => 'required|string|max:255',
                'link'  => 'required|string|max:255',
            ]);

            $link = $this->interface->find($linkId);

            $this->interface->edit($link, [
                'language_id' => $request->input('language_id') ?: null,
                'label'       => $request->input('label'),
                'link'        => $request->input('link'),
            ]);

            $link->refresh()->load('language');

            return $this->success([
                'id'          => $link->id,
                'language_id' => $link->language_id,
                'language'    => $link->language?->label,
                'label'       => $link->label,
                'link'        => $link->link,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function destroy(int $productId, int $linkId): JsonResponse
    {
        try {
            $this->interface->remove($linkId);
            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
