<?php

namespace App\Http\Controllers\Backoffice;

use App\Facades\Utils;
use App\Http\Controllers\Backoffice\Requests\StoreProductRequest;
use App\Http\Controllers\Backoffice\Requests\UpdateProductRequest;
use App\Interfaces\ProductInterface;
use App\Models\Category;
use App\Models\CustomerFieldType;
use App\Models\Language;
use App\Models\Product;
use App\Jobs\SyncProductToWooCommerce;
use App\Models\LanguageContent;
use App\Models\Media;
use App\Models\OrderProduct;
use App\Models\Partner;
use App\Models\ProductCustomerField;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\ProductFaq;
use App\Models\ProductLink;
use App\Models\ProductRelated;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class ProductController extends CrudController
{
    use AuthorizesRequests, ValidatesRequests;

    public ProductInterface $interface;
    public string $path;

    public function __construct(ProductInterface $interface)
    {
        $this->interface = $interface;
        $this->path = 'products';
    }

    public function index(): View
    {
        $user = Auth::user();
        $partners = [];

        if (in_array($user->role, ['god', 'admin', 'company'])) {
            $partners = Utils::map_collection(Partner::active());
        }

        return view('backoffice.' . $this->path . '.index', compact('partners'))
            ->with('path', $this->path);
    }

    public function data(Request $request) : JsonResponse {
        try {
            $user = Auth::user();
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters);

            if ($user->role === 'partner') {
                $elements->where('partner_id', $user->partner_id);
            }

            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit'])
                ->addColumn('created_at', function ($item) {
                    return Utils::data_long($item->created_at);
                })
                ->addColumn('product_code', function ($item) {
                    return '#' . $item->product_code;
                })
                ->addColumn('partner', function ($item) {
                    if (isset($item->partner)) {
                        return $item->partner->partner_name;
                    }
                    return ' -- ';
                })
                ->addColumn('category', function ($item) {
                    return $item->category->label ?? ' - ';
                })
                ->addColumn('pricing', function ($item) {
                    return "0 0 0";
                })
                ->addColumn('options', function ($item) {
                    return ' > ';
                })
                ->addColumn('status', function ($item) {
                    return view('backoffice.components.label', ['status' => $item->status()->status(), 'label' => $item->status()->label()])->render();
                })
                ->rawColumns(['status'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $user = Auth::user();

        $data = [
            'label' => $request->get('label'),
            'is_active' => 0,
        ];

        if ($user->role === 'partner') {
            $data['partner_id'] = $user->partner_id;
        } elseif ($request->has('partner_id')) {
            $data['partner_id'] = $request->get('partner_id');
        }

        $product = $this->interface->store($data);

        return $this->success(['redirect' => route($this->path . '.show', $product->id)]);
    }

    public function show(int $id)
    {
        $model = $this->interface->find($id);
        $this->authorizeAccess($model);
        $categories = Utils::map_collection(Category::where('is_active', 1));
        $languages = Language::where('is_active', 1)
            ->get()
            ->map(fn($l) => ['id' => $l->id, 'label' => $l->custom_label ?? $l->label, 'iso_code' => $l->iso_code])
            ->values()
            ->toArray();
        $fieldTypes = CustomerFieldType::orderBy('sort_order')->get();

        return view('backoffice.' . $this->path . '.show', compact('model', 'categories', 'languages', 'fieldTypes'))
            ->with('path', $this->path);
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $product = $this->interface->find($id);
            $this->authorizeAccess($product);

            match ($request->input('section')) {
                'settings'    => $this->updateSettings($product, $request),
                'duration'    => $this->updateDuration($product, $request),
                'categories'  => $this->updateCategories($product, $request),
                'public'      => $this->updatePublic($product, $request),
                'description' => $this->updateDescription($product, $request),
                default       => throw new \Exception('Sezione non valida'),
            };

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    private function updateSettings(Product $product, UpdateProductRequest $request): void
    {
        $data = ['label' => $request->label];
        if ($request->has('is_active')) {
            $data['is_active'] = $request->input('is_active', '0');
        }
        $this->interface->edit($product, $data);
    }

    private function updateDuration(Product $product, UpdateProductRequest $request): void
    {
        $days    = (int) $request->input('duration_days', 0);
        $hours   = (int) $request->input('duration_hours', 0);
        $minutes = (int) $request->input('duration_minutes', 0);

        $duration = ($days * 1440) + ($hours * 60) + $minutes;

        $this->interface->edit($product, [
            'duration'         => $duration,
            'duration_days'    => $days,
            'duration_hours'   => $hours,
            'duration_minutes' => $minutes,
        ]);
    }

    private function updateCategories(Product $product, UpdateProductRequest $request): void
    {
        $data = [];
        if ($request->has('category_id')) {
            $data['category_id'] = $request->category_id ?: null;
        }
        if (!empty($data)) {
            $this->interface->edit($product, $data);
        }
    }

    private function updatePublic(Product $product, UpdateProductRequest $request): void
    {
        $product->setContentFields([
            'meta_title'       => $request->meta_title,
            'meta_description' => $request->input('meta_description'),
        ]);
    }

    private function updateDescription(Product $product, UpdateProductRequest $request): void
    {
        $product->setContentFields([
            'description' => $request->input('description'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $product = $this->interface->find($id);
            $this->authorizeAccess($product);

            $hasOrders = OrderProduct::where('product_id', $id)->exists();
            if ($hasOrders) {
                return $this->error(['message' => 'Non è possibile eliminare il prodotto perché sono presenti prenotazioni associate.']);
            }

            // Relazioni morph (nessun FK cascade — eliminazione esplicita)
            LanguageContent::where('entity_type', Product::class)->where('entity_id', $id)->delete();
            Media::where('mediable_type', Product::class)->where('mediable_id', $id)->delete();

            // Relazioni dirette (eliminazione esplicita come garanzia, indipendentemente dal cascade FK)
            ProductLink::where('product_id', $id)->delete();
            ProductFaq::where('product_id', $id)->delete();
            ProductRelated::where('product_id', $id)->orWhere('related_product_id', $id)->delete();
            ProductCustomerField::where('product_id', $id)->delete();

            // Elimina il prodotto (cascade FK gestisce eventuali altre tabelle collegate)
            $this->interface->remove($id);

            return $this->success(['redirect' => route($this->path . '.index')]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function storeVariant(Request $request, int $id): JsonResponse
    {
        try {
            $product = $this->interface->find($id);
            $this->authorizeAccess($product);

            $data = $request->validate([
                'label'             => 'required|string|max:255',
                'description'       => 'nullable|string|max:500',
                'max_quantity'      => 'nullable|integer|min:1',
                'prices'            => 'required|array|min:1',
                'prices.*.label'    => 'required|string|max:255',
                'prices.*.price'    => 'required|numeric|min:0',
                'prices.*.vat_rate' => 'required|numeric|min:0|max:100',
            ]);

            $maxOrder = $product->variants()->max('sort_order') ?? 0;

            $variant = $product->variants()->create([
                'label'        => $data['label'],
                'description'  => $data['description'] ?? null,
                'max_quantity' => $data['max_quantity'] ?? null,
                'sort_order'   => $maxOrder + 1,
            ]);

            foreach ($data['prices'] as $row) {
                $variant->prices()->create([
                    'label'    => $row['label'],
                    'price'    => $row['price'],
                    'vat_rate' => $row['vat_rate'],
                ]);
            }

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function updateVariant(Request $request, int $id, int $variantId): JsonResponse
    {
        try {
            $product = $this->interface->find($id);
            $this->authorizeAccess($product);

            $data = $request->validate([
                'label'             => 'required|string|max:255',
                'description'       => 'nullable|string|max:500',
                'max_quantity'      => 'nullable|integer|min:1',
                'prices'            => 'required|array|min:1',
                'prices.*.id'       => 'nullable|integer',
                'prices.*.label'    => 'required|string|max:255',
                'prices.*.price'    => 'required|numeric|min:0',
                'prices.*.vat_rate' => 'required|numeric|min:0|max:100',
            ]);

            $variant = ProductVariant::where('product_id', $id)->findOrFail($variantId);
            $variant->update([
                'label'        => $data['label'],
                'description'  => $data['description'] ?? null,
                'max_quantity' => $data['max_quantity'] ?? null,
            ]);

            $keptIds = collect($data['prices'])->pluck('id')->filter()->values();
            $variant->prices()->whereNotIn('id', $keptIds)->delete();

            foreach ($data['prices'] as $row) {
                if (!empty($row['id'])) {
                    ProductVariantPrice::where('id', $row['id'])->update([
                        'label'    => $row['label'],
                        'price'    => $row['price'],
                        'vat_rate' => $row['vat_rate'],
                    ]);
                } else {
                    $variant->prices()->create([
                        'label'    => $row['label'],
                        'price'    => $row['price'],
                        'vat_rate' => $row['vat_rate'],
                    ]);
                }
            }

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function destroyVariant(int $id, int $variantId): JsonResponse
    {
        try {
            $product = $this->interface->find($id);
            $this->authorizeAccess($product);

            $variant = ProductVariant::where('product_id', $id)->findOrFail($variantId);
            $variant->prices()->delete();
            $variant->delete();

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function reorderVariants(Request $request, int $id): JsonResponse
    {
        try {
            $product = $this->interface->find($id);
            $this->authorizeAccess($product);

            $request->validate(['ordered_ids' => 'required|array']);

            foreach ($request->ordered_ids as $index => $variantId) {
                ProductVariant::where('id', $variantId)
                    ->where('product_id', $id)
                    ->update(['sort_order' => $index + 1]);
            }

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function getVariantTranslations(int $id, int $variantId): JsonResponse
    {
        try {
            $variant = ProductVariant::where('product_id', $id)->findOrFail($variantId);
            $languages = Language::where('is_active', 1)->get();

            $data = $languages->map(fn($lang) => [
                'language_id' => $lang->id,
                'language'    => $lang->label,
                'iso_code'    => $lang->iso_code,
                'label'       => $lang->iso_code === 'it'
                    ? $variant->label
                    : ($variant->contentField('label', $lang->iso_code) ?? ''),
                'description' => $lang->iso_code === 'it'
                    ? $variant->description
                    : ($variant->contentField('description', $lang->iso_code) ?? ''),
            ]);

            return $this->success(['data' => $data]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function saveVariantTranslations(Request $request, int $id, int $variantId): JsonResponse
    {
        try {
            $variant = ProductVariant::where('product_id', $id)->findOrFail($variantId);

            foreach ($request->input('translations', []) as $translation) {
                $lang = Language::find($translation['language_id']);
                if (!$lang) continue;

                if ($lang->iso_code === 'it') {
                    $variant->update([
                        'label'       => $translation['label'] ?? $variant->label,
                        'description' => $translation['description'] ?? $variant->description,
                    ]);
                    $variant->refresh();
                } else {
                    $variant->setContentFields([
                        'label'       => $translation['label'] ?? '',
                        'description' => $translation['description'] ?? '',
                    ], $lang->iso_code);
                }
            }

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function syncWooCommerce(int $id): JsonResponse
    {
        try {
            $product = $this->interface->find($id);
            $this->authorizeAccess($product);

            $company = $product->partner?->company;

            if (!$company || !$company->has_woocommerce) {
                return $this->error(['message' => 'L\'azienda non ha il plugin WooCommerce attivo']);
            }

            if (!$company->endpoint_woocommerce) {
                return $this->error(['message' => 'Endpoint WooCommerce non configurato per questa azienda']);
            }

            SyncProductToWooCommerce::dispatch($product, $company);

            return $this->success(['message' => 'Sincronizzazione avviata']);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    private function authorizeAccess($product): void
    {
        $user = Auth::user();

        if (in_array($user->role, ['god', 'admin'])) {
            return;
        }

        if ($user->role === 'partner' && $product->partner_id !== $user->partner_id) {
            abort(403);
        }

    }
}
