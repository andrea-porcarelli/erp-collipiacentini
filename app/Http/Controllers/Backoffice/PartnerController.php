<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Backoffice\Requests\StorePartnerRequest;
use App\Http\Controllers\Controller;
use App\Interfaces\PartnerInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rule;
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
        return view('backoffice.' . $this->path . '.index')
            ->with('path', $this->path);
    }

    public function show(int $id): View
    {
        $model = $this->interface->find($id);
        $hasOrders = $model->orders()->exists();

        return view('backoffice.' . $this->path . '.show', compact('model', 'hasOrders'))
            ->with('path', $this->path);
    }

    public function store(StorePartnerRequest $request): JsonResponse
    {
        $partner = $this->interface->store([
            'partner_name' => $request->get('partner_name'),
            'partner_code' => Str::upper(Str::substr(Str::slug($request->get('partner_name')), 0, 5)),
            'has_notify' => 0,
            'email_notify' => '',
            'is_active' => 0,
        ]);

        return $this->success(['redirect' => route($this->path . '.show', $partner->id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $partner = $this->interface->find($id);

            match ($request->input('section')) {
                'status' => $this->interface->edit($partner, [
                    'is_active' => $request->input('is_active'),
                ]),
                'info' => $this->validateAndEdit($partner, $request),
                'commissions' => $this->interface->edit($partner, [
                    'commission_presale_low'      => $request->input('commission_presale_low'),
                    'commission_presale_high'     => $request->input('commission_presale_high'),
                    'commission_miticko_fixed'    => $request->input('commission_miticko_fixed'),
                    'commission_miticko_variable' => $request->input('commission_miticko_variable'),
                    'commission_payment'          => $request->input('commission_payment'),
                ]),
                default => throw new \Exception('Sezione non valida'),
            };

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    private function validateAndEdit($partner, Request $request): void
    {
        $this->validate($request, [
            'domain_name' => [
                'nullable',
                'string',
                Rule::unique('partners', 'domain_name')->ignore($partner->id),
            ],
        ], [
            'domain_name.unique' => 'Il dominio inserito è già associato a un altro partner',
        ]);

        $hasOrders = $partner->orders()->exists();
        $newCode = $request->input('partner_code');

        if ($hasOrders && $newCode !== $partner->partner_code) {
            throw new \Exception('Il codice partner non può essere modificato: esistono già ordini registrati.');
        }

        $this->interface->edit($partner, [
            'partner_name'  => $request->input('partner_name'),
            'partner_code'  => $hasOrders ? $partner->partner_code : $newCode,
            'email_notify'  => $request->input('email_notify'),
            'sale_method'   => $request->input('sale_method'),
            'domain_name'   => $request->input('domain_name') ?: null,
        ]);
    }

    public function data(Request $request) : JsonResponse {
        try {
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters);
            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit', 'status'])
                ->addColumn('partner_code', function ($item) {
                    return (string) $item->partner_code;
                })
                ->rawColumns(['status'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
