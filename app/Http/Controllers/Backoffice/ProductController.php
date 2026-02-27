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
use App\Models\Company;
use App\Models\LanguageContent;
use App\Models\Media;
use App\Models\OrderProduct;
use App\Models\Partner;
use App\Models\ProductCustomerField;
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
        $companies = [];
        $partners = [];

        if (in_array($user->role, ['god', 'admin'])) {
            $companies = Company::where('is_active', 1)->get()->map(function ($item) {
                return ['id' => $item->id, 'label' => $item->company_name];
            })->values()->toArray();
        } elseif ($user->role === 'company') {
            $partners = Utils::map_collection(Partner::active()->where('company_id', $user->company_id));
        }

        return view('backoffice.' . $this->path . '.index', compact('companies', 'partners'))
            ->with('path', $this->path);
    }

    public function subCategoriesByCategory(int $categoryId): JsonResponse
    {
        $subCategories = Category::where('category_id', $categoryId)
            ->where('is_active', 1)
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->label])
            ->values()
            ->toArray();

        return response()->json($subCategories);
    }

    public function partnersByCompany(int $companyId): JsonResponse
    {
        $partners = Partner::active()->where('company_id', $companyId)->get()->map(function ($item) {
            return ['id' => $item->id, 'label' => $item->partner_name];
        })->values()->toArray();

        return response()->json($partners);
    }

    public function data(Request $request) : JsonResponse {
        try {
            $user = Auth::user();
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters);

            if ($user->role === 'partner') {
                $elements->where('partner_id', $user->partner_id);
            } elseif ($user->role === 'company') {
                $elements->whereHas('partner', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit', 'status'])
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
        $categories = Utils::map_collection(Category::where('partner_id', $model->partner_id)
            ->whereNull('category_id')
            ->where('is_active', 1));
        $languages = Utils::map_collection(Language::where('is_active', 1));
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
                'settings'   => $this->updateSettings($product, $request),
                'duration'   => $this->updateDuration($product, $request),
                'categories' => $this->updateCategories($product, $request),
                'public'     => $this->updatePublic($product, $request),
                default      => throw new \Exception('Sezione non valida'),
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
        if ($request->has('sub_category_id')) {
            $data['sub_category_id'] = $request->sub_category_id ?: null;
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

        if ($user->role === 'company' && (!$product->partner || $product->partner->company_id !== $user->company_id)) {
            abort(403);
        }
    }
}
