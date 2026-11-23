<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\WordpressSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function trigger(Request $request, WordpressSyncService $sync): JsonResponse
    {
        $partner = $request->get('partner');

        if (! $partner instanceof Partner) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'La sincronizzazione completa è disponibile solo per i partner collegati via plugin WordPress',
            ], 403);
        }

        $count = $sync->triggerFullSync($partner);

        return response()->json([
            'ok' => true,
            'queued' => $count,
        ]);
    }
}
