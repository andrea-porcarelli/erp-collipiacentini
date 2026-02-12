<?php

namespace App\Http\Controllers\Backoffice;

use App\Facades\Utils;
use App\Http\Controllers\Backoffice\Requests\StoreProductRequest;
use App\Interfaces\ProductInterface;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return view('backoffice.' . $this->path . '.show', compact('model'))
            ->with('path', $this->path);
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
