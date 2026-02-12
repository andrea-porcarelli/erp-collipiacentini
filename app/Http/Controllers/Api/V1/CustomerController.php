<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErpCustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $request->get('company');
        $perPage = min((int) $request->query('per_page', 100), 500);
        $page = max((int) $request->query('page', 1), 1);

        $customers = Customer::where('company_id', $company->id)
            ->with('country')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'customers' => ErpCustomerResource::collection($customers->items()),
            'pagination' => [
                'page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'total_pages' => $customers->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $company = $request->get('company');
        $numericId = $this->extractNumericId($id);

        $customer = Customer::where('company_id', $company->id)
            ->with('country')
            ->find($numericId);

        if (!$customer) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Cliente non trovato',
            ], 404);
        }

        return response()->json(new ErpCustomerResource($customer));
    }

    private function extractNumericId(string $id): int
    {
        if (str_starts_with($id, 'ERP-CUST-')) {
            return (int) substr($id, 9);
        }

        return (int) $id;
    }
}
