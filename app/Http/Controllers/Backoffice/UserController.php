<?php

namespace App\Http\Controllers\Backoffice;

use App\Facades\Utils;
use App\Http\Controllers\Backoffice\Requests\StoreUserRequest;
use App\Interfaces\UserInterface;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends CrudController
{
    use AuthorizesRequests, ValidatesRequests;

    public UserInterface $interface;
    public string $path;

    public function __construct(UserInterface $interface)
    {
        $this->interface = $interface;
        $this->path = 'users';
    }

    public function index(): View
    {
        $companies = Company::where('is_active', 1)->get()->map(function ($item) {
            return ['id' => $item->id, 'label' => $item->company_name];
        })->values()->toArray();

        $partners = Utils::map_collection(Partner::active());

        $roles = [
            ['id' => 'god', 'label' => 'God'],
            ['id' => 'admin', 'label' => 'Admin'],
            ['id' => 'operator', 'label' => 'Operatore'],
            ['id' => 'partner', 'label' => 'Partner'],
            ['id' => 'company', 'label' => 'Azienda'],
        ];

        return view('backoffice.' . $this->path . '.index', compact('companies', 'partners', 'roles'))
            ->with('path', $this->path);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = [
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => $request->get('password'),
            'role' => $request->get('role'),
        ];

        if ($request->get('role') === 'partner') {
            $data['partner_id'] = $request->get('partner_id');
        }

        if ($request->get('role') === 'company') {
            $data['company_id'] = $request->get('company_id');
        }

        $user = $this->interface->store($data);

        return $this->success(['redirect' => route($this->path . '.show', $user->id)]);
    }

    public function data(Request $request) : JsonResponse {
        try {
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters);
            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit', 'status'])
                ->addColumn('role', function ($item) {
                    return ucfirst($item->role);
                })
                ->addColumn('association', function ($item) {
                    if ($item->role === 'partner' && $item->partner) {
                        return $item->partner->partner_name;
                    }
                    if ($item->role === 'company' && $item->company) {
                        return $item->company->company_name;
                    }
                    return ' - ';
                })
                ->rawColumns(['status'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
