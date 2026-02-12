<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Backoffice\Requests\StoreCompanyRequest;
use App\Http\Controllers\Controller;
use App\Interfaces\CompanyInterface;
use App\Interfaces\OrderInterface;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyController extends CrudController
{
    use AuthorizesRequests, ValidatesRequests;

    public CompanyInterface $interface;
    public string $path;

    public function __construct(CompanyInterface $interface)
    {
        $this->interface = $interface;
        $this->path = 'companies';
    }

    public function index(): View
    {
        return view('backoffice.' . $this->path . '.index')
            ->with('path', $this->path);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = $this->interface->store([
            'company_name' => $request->get('company_name'),
            'company_code' => Str::upper(Str::substr(Str::slug($request->get('company_name')), 0, 5)),
            'vat_number' => $request->get('vat_number'),
            'is_active' => 0,
        ]);

        return $this->success(['redirect' => route($this->path . '.show', $company->id)]);
    }

    public function generateToken(int $id): JsonResponse
    {
        try {
            $company = $this->interface->find($id);
            $token = Str::random(64);
            $this->interface->edit($company, ['token' => $token]);

            return $this->success(['token' => $token]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function data(Request $request) : JsonResponse {
        try {
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters);
            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit', 'status'])
                ->addColumn('has_whitelabel', function ($item) {
                    return view('backoffice.components.label', [
                        'icon' => $item->has_whitelabel ? 'check' : 'times',
                        'status' => $item->has_whitelabel ? 'success' : 'error',
                        'label' => $item->has_whitelabel ? 'Attivo' : 'Non attivo']);
                })
                ->rawColumns(['status'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
