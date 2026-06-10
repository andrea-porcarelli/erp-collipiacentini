<?php

namespace App\Http\Controllers\Backoffice;

use App\Facades\Utils;
use App\Http\Controllers\Controller;
use App\Interfaces\CustomerInterface;
use App\Models\CustomerConsent;
use App\Models\PartnerConsent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function show(int $id): View
    {
        $model = $this->interface->find($id);
        $model->loadMissing('country');

        $orders = $model->orders()
            ->with(['partner:id,partner_name', 'orderProducts.product'])
            ->orderByDesc('created_at')
            ->get();

        $customerConsents = CustomerConsent::with('partnerConsent')
            ->where('customer_id', $model->id)
            ->get()
            ->filter(fn ($cc) => $cc->partnerConsent !== null)
            ->sortBy(fn ($cc) => $cc->partnerConsent->position)
            ->values()
            ->map(function ($cc) {
                $pc = $cc->partnerConsent;
                if ($pc->code === PartnerConsent::CODE_TERMS) {
                    $label = 'Privacy & Cookie Policy / Termini e Condizioni';
                } else {
                    $raw = trim(strip_tags($pc->contentField('content', 'it') ?? ''));
                    $label = \Illuminate\Support\Str::limit($raw, 80, '…') ?: '—';
                }

                return [
                    'label'         => $label,
                    'accepted'      => (bool) $cc->accepted,
                    'subscribed_at' => $cc->subscribed_at,
                    'expires_at'    => $cc->expires_at,
                ];
            });

        return view('backoffice.' . $this->path . '.show', compact('model', 'orders', 'customerConsents'))
            ->with('path', $this->path);
    }

    public function data(Request $request) : JsonResponse {
        try {
            $user = Auth::user();
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters);

            if ($user->role === 'company') {
                $elements->where('company_id', $user->company_id);
            } elseif (in_array($user->role, ['partner', 'admin'])) {
                $partnerId = $user->partner_id;
                $elements->where(function ($q) use ($partnerId) {
                    $q->where('partner_id', $partnerId)
                      ->orWhereHas('orders.orderProducts.product', function ($sub) use ($partnerId) {
                          $sub->where('partner_id', $partnerId);
                      });
                });
            }
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
