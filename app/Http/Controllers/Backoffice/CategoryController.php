<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Controller;
use App\Interfaces\CategoryInterface;
use App\Interfaces\OrderInterface;
use App\Interfaces\ProductInterface;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public CategoryInterface $interface;
    public string $path;

    public function __construct(CategoryInterface $interface)
    {
        $this->interface = $interface;
        $this->path = 'categories';
    }

    public function index(): View
    {
        $statuses = OrderStatus::statuses();
        return view('backoffice.' . $this->path . '.index', compact('statuses'));
    }

    public function data(Request $request) : JsonResponse {
        try {
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters);
            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit', 'status'])
                ->addColumn('created_at', function ($item) {
                    return Utils::data_long($item->created_at);
                })
                ->addColumn('partner', function ($item) {
                    return $item->partner->partner_name;
                })
                ->addColumn('category', function ($item) {
                    return $item->label;
                })
                ->addColumn('products', function ($item) {
                    return $item->products()->count();
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
