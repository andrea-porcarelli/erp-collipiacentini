<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Controller;
use App\Interfaces\CompanyInterface;
use App\Interfaces\OrderInterface;
use App\Interfaces\PartnerInterface;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerController extends CrudController
{
    use AuthorizesRequests, ValidatesRequests;

    public PartnerInterface $interface;
    public string $path;

    public function __construct(PartnerInterface $interface)
    {
        $this->interface = $interface;
        $this->path = 'partners';
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
                ->addColumn('partner_code', function ($item) {
                    return (string) $item->partner_code;
                })
                ->addColumn('company', function ($item) {
                    return $item->company->company_name;
                })
                ->rawColumns(['status'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
