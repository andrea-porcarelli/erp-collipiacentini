<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Backoffice\Requests\StorePartnerRequest;
use App\Http\Controllers\Controller;
use App\Interfaces\CompanyInterface;
use App\Interfaces\OrderInterface;
use App\Interfaces\PartnerInterface;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $companies = Company::where('is_active', 1)->get()->map(function ($item) {
            return ['id' => $item->id, 'label' => $item->company_name];
        })->values()->toArray();

        return view('backoffice.' . $this->path . '.index', compact('companies'))
            ->with('path', $this->path);
    }

    public function store(StorePartnerRequest $request): JsonResponse
    {
        $partner = $this->interface->store([
            'partner_name' => $request->get('partner_name'),
            'partner_code' => Str::upper(Str::substr(Str::slug($request->get('partner_name')), 0, 5)),
            'company_id' => $request->get('company_id'),
            'has_notify' => 0,
            'email_notify' => '',
            'is_active' => 0,
        ]);

        return $this->success(['redirect' => route($this->path . '.show', $partner->id)]);
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
