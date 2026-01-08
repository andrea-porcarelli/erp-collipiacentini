<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Controller;
use App\Interfaces\OrderInterface;
use App\Interfaces\ProductInterface;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        return view('backoffice.' . $this->path . '.index')
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
                    return $item->partner->company->company_name . ' > ' . $item->partner->partner_name;
                })
                ->addColumn('category', function ($item) {
                    return $item->category->label;
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
}
