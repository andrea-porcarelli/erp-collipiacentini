<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Controller;
use App\Interfaces\CustomerInterface;
use App\Interfaces\OrderInterface;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public CustomerInterface $interface;
    public string $path;

    public function __construct(CustomerInterface $interface)
    {
        $this->interface = $interface;
        $this->path = 'customers';
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
                ->addColumn('orders', function ($item) {
                    return $item->orders()->count();
                })
                ->addColumn('full_name', function ($item) {
                    return $item->full_name;
                })
                ->addColumn('contacts', function ($item) {
                    return "
                        <span class='fa fa-phone'></span> " . $item->phone . "<br />
                        <span class='fa fa-envelope'></span> " . $item->email . "<br />
                        ";
                })
                ->addColumn('address', function ($item) {
                    return $item->full_address;
                })
                ->addColumn('options', function ($item) {
                    return ' > ';
                })
                ->rawColumns(['options', 'contacts'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
