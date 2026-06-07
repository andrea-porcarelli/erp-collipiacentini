<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Backoffice\Requests\StorePartnerRequest;
use App\Http\Controllers\Controller;
use App\Interfaces\PartnerInterface;
use App\Models\Media;
use App\Models\Partner;
use App\Models\PartnerBilling;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $model->loadMissing('billing', 'logo');
        $hasOrders = $model->orders()->exists();

        return view('backoffice.' . $this->path . '.show', compact('model', 'hasOrders'))
            ->with('path', $this->path);
    }

    public function uploadLogo(Request $request, Partner $partner): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
        ]);

        if ($existing = $partner->logo()->first()) {
            Storage::disk('public')->delete($existing->file_path);
            $existing->delete();
        }

        $file = $request->file('image');
        $path = $file->store("partners/{$partner->id}", 'public');

        $media = Media::create([
            'mediable_type' => Partner::class,
            'mediable_id'   => $partner->id,
            'media_type'    => 'logo',
            'file_name'     => $file->getClientOriginalName(),
            'file_path'     => $path,
            'file_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),
        ]);

        return response()->json([
            'id'        => $media->id,
            'file_name' => $media->file_name,
            'url'       => asset('storage/' . $media->file_path),
        ]);
    }

    public function deleteLogo(Partner $partner): JsonResponse
    {
        if ($logo = $partner->logo()->first()) {
            Storage::disk('public')->delete($logo->file_path);
            $logo->delete();
        }

        return response()->json(['ok' => true]);
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
                    'commission_presale_low'       => $request->input('commission_presale_low'),
                    'commission_presale_high'      => $request->input('commission_presale_high'),
                    'commission_presale_threshold' => $request->input('commission_presale_threshold'),
                    'commission_miticko_fixed'     => $request->input('commission_miticko_fixed'),
                    'commission_miticko_variable'  => $request->input('commission_miticko_variable'),
                    'commission_payment'           => $request->input('commission_payment'),
                ]),
                'billing' => PartnerBilling::updateOrCreate(
                    ['partner_id' => $partner->id],
                    $request->only([
                        'legal_name', 'vat_number', 'tax_code',
                        'street_address', 'postal_code', 'city', 'province', 'country',
                        'pec_email', 'sdi_code',
                        'iban', 'tax_regime',
                    ]),
                ),
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
            'css_style' => ['nullable', Rule::in(Partner::CSS_STYLES)],
        ], [
            'domain_name.unique' => 'Il dominio inserito è già associato a un altro partner',
            'css_style.in' => 'Stile CSS non valido',
        ]);

        $hasOrders = $partner->orders()->exists();
        $newCode = $request->input('partner_code');

        if ($hasOrders && $request->has('partner_code') && $newCode !== $partner->partner_code) {
            throw new \Exception('Il codice partner non può essere modificato: esistono già ordini registrati.');
        }

        $this->interface->edit($partner, [
            'partner_name'  => $request->input('partner_name'),
            'partner_code'  => $hasOrders ? $partner->partner_code : $newCode,
            'email_notify'  => $request->input('email_notify'),
            'sale_method'   => $request->input('sale_method'),
            'domain_name'   => $request->input('domain_name') ?: null,
            'css_style'     => $request->input('css_style') ?: 'Miticko',
        ]);
    }

    public function data(Request $request) : JsonResponse {
        try {
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters)
                ->with('billing')
                ->orderByDesc('id');

            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit', 'status'])
                ->addColumn('partner_code', function ($item) {
                    return (string) $item->partner_code;
                })
                ->addColumn('domain', function ($item) {
                    if (!$item->domain_name) {
                        return '—';
                    }
                    $url = preg_match('#^https?://#i', $item->domain_name)
                        ? $item->domain_name
                        : 'https://' . $item->domain_name;
                    return '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer">'
                        . e($item->domain_name)
                        . ' <i class="fa-regular fa-arrow-up-right-from-square ms-1 small"></i></a>';
                })
                ->addColumn('contacts', function ($item) {
                    $lines = [];
                    if ($item->email_notify) {
                        $lines[] = '<i class="fa-regular fa-envelope text-secondary me-1"></i>' . e($item->email_notify);
                    }
                    if ($item->billing?->pec_email) {
                        $lines[] = '<i class="fa-regular fa-shield-check text-secondary me-1"></i>' . e($item->billing->pec_email) . ' <span class="text-secondary small">(PEC)</span>';
                    }
                    $addressParts = array_filter([
                        $item->billing?->street_address,
                        trim(($item->billing?->postal_code ?? '') . ' ' . ($item->billing?->city ?? '')),
                        $item->billing?->province,
                    ]);
                    if (!empty($addressParts)) {
                        $lines[] = '<i class="fa-regular fa-location-dot text-secondary me-1"></i>' . e(implode(', ', $addressParts));
                    }
                    return empty($lines) ? '—' : implode('<br>', $lines);
                })
                ->addColumn('commissions', function ($item) {
                    $rows = [];
                    $threshold = $item->commission_presale_threshold;
                    if (!is_null($item->commission_presale_low) || !is_null($item->commission_presale_high) || !is_null($threshold)) {
                        $rows[] = 'Presale: '
                            . '<span title="sotto soglia">'  . self::fmtEuro($item->commission_presale_low)  . '</span>'
                            . ' / '
                            . '<span title="sopra soglia">' . self::fmtEuro($item->commission_presale_high) . '</span>'
                            . ' <span class="text-secondary small">soglia ' . self::fmtEuro($threshold) . '</span>';
                    }
                    if (!is_null($item->commission_miticko_fixed) || !is_null($item->commission_miticko_variable)) {
                        $rows[] = 'Miticko: '
                            . self::fmtEuro($item->commission_miticko_fixed) . ' fissa + '
                            . self::fmtPercent($item->commission_miticko_variable) . ' var.';
                    }
                    if (!is_null($item->commission_payment)) {
                        $rows[] = 'Pagamento: ' . self::fmtPercent($item->commission_payment);
                    }
                    return empty($rows) ? '<span class="text-secondary">—</span>' : implode('<br>', $rows);
                })
                ->rawColumns(['status', 'domain', 'contacts', 'commissions'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    private static function fmtEuro($value): string
    {
        return is_null($value) ? '—' : number_format((float) $value, 2, ',', '.') . ' €';
    }

    private static function fmtPercent($value): string
    {
        return is_null($value) ? '—' : rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',') . ' %';
    }
}
