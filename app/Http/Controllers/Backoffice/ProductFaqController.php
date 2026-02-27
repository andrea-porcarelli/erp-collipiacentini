<?php

namespace App\Http\Controllers\Backoffice;

use App\Interfaces\ProductFaqInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductFaqController extends CrudController
{
    public ProductFaqInterface $interface;
    public string $path = 'product-faqs';

    public function __construct(ProductFaqInterface $interface)
    {
        $this->interface = $interface;
    }

    public function index(int $productId): JsonResponse
    {
        try {
            $faqs = $this->interface->filters(['product_id' => $productId])
                ->with('language')
                ->get()
                ->map(fn($faq) => [
                    'id'          => $faq->id,
                    'language_id' => $faq->language_id,
                    'language'    => $faq->language?->label,
                    'question'    => $faq->question,
                    'answer'      => $faq->answer,
                ]);

            return $this->success(['data' => $faqs]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        try {
            $request->validate([
                'question' => 'required|string',
                'answer'   => 'required|string',
            ]);

            $faq = $this->interface->store([
                'product_id'  => $productId,
                'language_id' => $request->input('language_id') ?: null,
                'question'    => $request->input('question'),
                'answer'      => $request->input('answer'),
            ]);

            $faq->load('language');

            return $this->success([
                'id'          => $faq->id,
                'language_id' => $faq->language_id,
                'language'    => $faq->language?->label,
                'question'    => $faq->question,
                'answer'      => $faq->answer,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function update(Request $request, int $productId, int $faqId): JsonResponse
    {
        try {
            $request->validate([
                'question' => 'required|string',
                'answer'   => 'required|string',
            ]);

            $faq = $this->interface->find($faqId);

            $this->interface->edit($faq, [
                'language_id' => $request->input('language_id') ?: null,
                'question'    => $request->input('question'),
                'answer'      => $request->input('answer'),
            ]);

            $faq->refresh()->load('language');

            return $this->success([
                'id'          => $faq->id,
                'language_id' => $faq->language_id,
                'language'    => $faq->language?->label,
                'question'    => $faq->question,
                'answer'      => $faq->answer,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function destroy(int $productId, int $faqId): JsonResponse
    {
        try {
            $this->interface->remove($faqId);
            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
