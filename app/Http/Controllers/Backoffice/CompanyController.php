<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Controller;
use App\Interfaces\OrderInterface;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public OrderInterface $interface;

    public function __construct(OrderInterface $interface)
    {
        $this->interface = $interface;
    }

    public function index(): View
    {
        $statuses = OrderStatus::statuses();
        return view('backoffice.orders.index', compact('statuses'));
    }

    public function data(Request $request) : JsonResponse {
        try {
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters);
            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit', 'status'])
                ->addColumn('created_at', function ($item) {
                    return Utils::data_long($item->created_at);
                })
                ->addColumn('order_number', function ($item) {
                    return '#' . $item->order_number;
                })
                ->addColumn('customer', function ($item) {
                    return $item->customer->full_name;
                })
                ->addColumn('timing', function ($item) {
                    return "10:00";
                })
                ->addColumn('details', function ($item) {
                    return "Visita guidata";
                })
                ->addColumn('type', function ($item) {
                    return "2 completi + 1 ridotto";
                })
                ->addColumn('status', function ($item) {
                    return view('backoffice.orders.components.status', ['item' => $item])->render();
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
