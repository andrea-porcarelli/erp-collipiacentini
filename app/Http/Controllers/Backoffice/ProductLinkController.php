<?php

namespace App\Http\Controllers\Backoffice;

use App\Interfaces\ProductLinkInterface;
use App\Models\Language;
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
                ->get()
                ->map(fn($link) => [
                    'id'    => $link->id,
                    'label' => $link->label,
                    'link'  => $link->link,
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
                'link'  => 'required|url|max:255',
            ]);

            $link = $this->interface->store([
                'product_id' => $productId,
                'label'      => $request->input('label'),
                'link'       => $request->input('link'),
            ]);

            return $this->success([
                'id'    => $link->id,
                'label' => $link->label,
                'link'  => $link->link,
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
                'label' => $request->input('label'),
                'link'  => $request->input('link'),
            ]);

            $link->refresh();

            return $this->success([
                'id'    => $link->id,
                'label' => $link->label,
                'link'  => $link->link,
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

    public function getTranslations(int $productId, int $linkId): JsonResponse
    {
        try {
            $link = $this->interface->find($linkId);
            $languages = Language::where('is_active', 1)->get();

            $data = $languages->map(fn($lang) => [
                'language_id' => $lang->id,
                'language'    => $lang->label,
                'iso_code'    => $lang->iso_code,
                'label'       => $lang->iso_code === 'it'
                    ? $link->label
                    : ($link->contentField('label', $lang->iso_code) ?? ''),
                'link'        => $lang->iso_code === 'it'
                    ? $link->link
                    : ($link->contentField('link', $lang->iso_code) ?? ''),
            ]);

            return $this->success(['data' => $data]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function saveTranslations(Request $request, int $productId, int $linkId): JsonResponse
    {
        try {
            $link = $this->interface->find($linkId);

            foreach ($request->input('translations', []) as $translation) {
                $lang = Language::find($translation['language_id']);
                if (!$lang) continue;

                if ($lang->iso_code === 'it') {
                    $this->interface->edit($link, [
                        'label' => $translation['label'] ?? $link->label,
                        'link'  => $translation['link'] ?? $link->link,
                    ]);
                    $link->refresh();
                } else {
                    $link->setContentFields([
                        'label' => $translation['label'] ?? '',
                        'link'  => $translation['link'] ?? '',
                    ], $lang->iso_code);
                }
            }

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }
}
