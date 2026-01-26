<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Backoffice\Requests\StoreProductRequest;
use App\Http\Controllers\Controller;
use App\Interfaces\OrderInterface;
use App\Interfaces\ProductInterface;
use App\Models\Order;
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
        $partners = null;
        if (Auth::user()->role === 'god') {
            $partners = Utils::map_collection(Partner::active());
        }
        return view('backoffice.' . $this->path . '.index', compact('partners'))
            ->with('path', $this->path);
    }

    public function data(Request $request) : JsonResponse {
        try {
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters);
            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit', 'status'])
                ->addColumn('created_at', function ($item) {
                    return Utils::data_long($item->created_at);
                })
                ->addColumn('product_code', function ($item) {
                    return '#' . $item->product_code;
                })
                ->addColumn('partner', function ($item) {
//                    if (isset($item->company)) {
//                        return $item->partner->company->company_name . ' > ' . $item->partner->partner_name;
//                    }
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
        $request->validate([
            'label' => 'required|unique:products,label',
        ], [
            'label.required' => 'Il nome del prodotto è obbligatorio',
            'label.unique' => 'Il nome del prodotto scelto è già stato usato',
        ]);


        $product = $this->interface->store([
            'label' => $request->get('label'),
            'is_active' => 0
        ]);

        return $this->success(['redirect' => route($this->path . '.show', $product->id)]);
    }
}
