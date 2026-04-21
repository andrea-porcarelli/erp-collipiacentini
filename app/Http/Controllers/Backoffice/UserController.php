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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function index(): View|RedirectResponse
    {
        if (Auth::user()->role == 'partner') {
            return back()->withErrors('Unauthorized access');
        }
        $companies = Company::where('is_active', 1)->get()->map(function ($item) {
            return ['id' => $item->id, 'label' => $item->company_name];
        })->values()->toArray();

        $partners = Utils::map_collection(Partner::active());

        $roles = [
            ['id' => 'god', 'label' => 'God'],
            ['id' => 'admin', 'label' => 'Admin'],
            ['id' => 'operator', 'label' => 'Operatore'],
            ['id' => 'partner', 'label' => 'Partner'],
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

            $elements = $this->interface->filters($filters)
            ->when(!in_array(Auth::user()->role, ['god']), function($q) {
                if (Auth::user()->role == 'admin') {
                    $q->whereIn('role', ['admin','partner'])
                        ->where('partner_id', Auth::user()->partner_id);
                }
            });
            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['impersonate', 'edit', 'status'])
                ->addColumn('role', function ($item) {
                    if ($item->role === 'partner') {
                        return "Collaboratore";
                    }
                    if ($item->role === 'admin') {
                        return "Proprietario";
                    }
                    return ucfirst($item->role);
                })
                ->addColumn('association', function ($item) {
                    if (in_array($item->role, ['partner', 'admin']) && $item->partner) {
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
