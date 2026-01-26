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

class OrderController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public OrderInterface $interface;
    public string $path;

    public function __construct(OrderInterface $interface)
    {
        $this->interface = $interface;
        $this->path = 'orders';
    }

    public function index(): View
    {
        $statuses = OrderStatus::statuses();
        return view('backoffice.' . $this->path . '.index', compact('statuses'))
            ->with('path', $this->path);
    }

    public function data(Request $request) : JsonResponse {
        try {
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters)->orderBy('created_at', 'desc');
            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit', 'status'])
                ->addColumn('created_at', function ($item) {
                    return Utils::data($item->product_data);
                })
                ->addColumn('order_number', function ($item) {
                    return '#' . $item->order_number;
                })
                ->addColumn('customer', function ($item) {
                    return $item->customer->full_name;
                })
                ->addColumn('timing', function ($item) {
                    return $item->product_time;
                })
                ->addColumn('details', function ($item) {
                    return $item->product_label;
                })
                ->addColumn('type', function ($item) {
                    return "2 completi + 1 ridotto";
                })
                ->addColumn('status', function ($item) {
                    $order_status = $item->order_status;
                    return view('backoffice.components.label', ['icon' => $order_status->icon(), 'status' => $order_status->status(), 'label' => $order_status->label()])->render();
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
