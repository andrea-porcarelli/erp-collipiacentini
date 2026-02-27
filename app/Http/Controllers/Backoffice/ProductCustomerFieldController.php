<?php

namespace App\Http\Controllers\Backoffice;

use App\Interfaces\ProductCustomerFieldInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductCustomerFieldController extends CrudController
{
    public ProductCustomerFieldInterface $interface;
    public string $path = 'product-customer-fields';

    public function __construct(ProductCustomerFieldInterface $interface)
    {
        $this->interface = $interface;
    }

    /**
     * Sostituisce interamente la configurazione dei campi cliente per il prodotto.
     * Accetta: { fields: [{customer_field_type_id: 1, is_required: true}, ...] }
     * I campi non presenti nell'array vengono rimossi.
     */
    public function sync(Request $request, int $productId): JsonResponse
    {
        try {
            $request->validate([
                'fields'                              => 'present|array',
                'fields.*.customer_field_type_id'     => 'required|integer|exists:customer_field_types,id',
                'fields.*.is_required'                => 'required|in:0,1',
            ]);

            DB::transaction(function () use ($request, $productId) {
                // Rimuovi tutte le configurazioni esistenti per questo prodotto
                $this->interface->filters(['product_id' => $productId])->delete();

                // Ricrea con i nuovi dati
                foreach ($request->input('fields', []) as $field) {
                    $this->interface->store([
                        'product_id'              => $productId,
                        'customer_field_type_id'  => $field['customer_field_type_id'],
                        'is_required'             => (bool) $field['is_required'],
                    ]);
                }
            });

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }
}
