<?php

namespace App\Interfaces;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface CalendarInterface
{
    /**
     * Ritorna, per la settimana che parte da $weekStart, l'elenco dei 7 giorni
     * con conteggio ordini per il partner richiesto.
     * Ogni item: ['date' => 'Y-m-d', 'orders_count' => int, 'has_bookings' => bool].
     */
    public function weekOverview(int $partnerId, Carbon $weekStart): Collection;

    /**
     * Ritorna gli slot del giorno per un partner, aggregati per prodotto o per fascia oraria.
     * $groupBy: 'product' | 'slot'.
     */
    public function daySlots(int $partnerId, string $date, string $groupBy): Collection;

    /**
     * Ritorna gli ordini di uno specifico slot (product + date + time) con i filtri
     * applicati dal pannello destro.
     */
    public function slotOrders(int $partnerId, int $productId, string $date, string $time, array $filters): Collection;
}
