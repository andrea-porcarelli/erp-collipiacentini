<?php

namespace App\Http\Controllers\Backoffice;

use App\Interfaces\ProductFaqInterface;
use App\Models\Language;
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
                ->get()
                ->map(fn($faq) => [
                    'id'       => $faq->id,
                    'question' => $faq->question,
                    'answer'   => $faq->answer,
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
                'product_id' => $productId,
                'question'   => $request->input('question'),
                'answer'     => $request->input('answer'),
            ]);

            return $this->success([
                'id'       => $faq->id,
                'question' => $faq->question,
                'answer'   => $faq->answer,
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
                'question' => $request->input('question'),
                'answer'   => $request->input('answer'),
            ]);

            $faq->refresh();

            return $this->success([
                'id'       => $faq->id,
                'question' => $faq->question,
                'answer'   => $faq->answer,
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

    public function getTranslations(int $productId, int $faqId): JsonResponse
    {
        try {
            $faq = $this->interface->find($faqId);
            $languages = Language::where('is_active', 1)->get();

            $data = $languages->map(fn($lang) => [
                'language_id' => $lang->id,
                'language'    => $lang->label,
                'iso_code'    => $lang->iso_code,
                'question'    => $lang->iso_code === 'it'
                    ? $faq->question
                    : ($faq->contentField('question', $lang->iso_code) ?? ''),
                'answer'      => $lang->iso_code === 'it'
                    ? $faq->answer
                    : ($faq->contentField('answer', $lang->iso_code) ?? ''),
            ]);

            return $this->success(['data' => $data]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function saveTranslations(Request $request, int $productId, int $faqId): JsonResponse
    {
        try {
            $faq = $this->interface->find($faqId);

            foreach ($request->input('translations', []) as $translation) {
                $lang = Language::find($translation['language_id']);
                if (!$lang) continue;

                if ($lang->iso_code === 'it') {
                    $this->interface->edit($faq, [
                        'question' => $translation['question'] ?? $faq->question,
                        'answer'   => $translation['answer'] ?? $faq->answer,
                    ]);
                    $faq->refresh();
                } else {
                    $faq->setContentFields([
                        'question' => $translation['question'] ?? '',
                        'answer'   => $translation['answer'] ?? '',
                    ], $lang->iso_code);
                }
            }

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }
}
