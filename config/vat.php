<?php

/**
 * Aliquote IVA standard italiane (DPR 633/1972) + nature di esenzione / non imponibilità
 * previste per la fattura elettronica (SdI / FatturaPA 1.2).
 * Fonte unica consumata dal componente <x-vat-select>.
 *
 * Il value salvato nel DB è il campo `rate` (string: aliquota numerica o codice natura "N*").
 * Il campo `label` è quello mostrato nella select.
 */
return [
    'rates' => [
        // Aliquote standard
        ['rate' => '22', 'label' => '22% – Aliquota ordinaria'],
        ['rate' => '10', 'label' => '10% – Aliquota ridotta'],
        ['rate' => '5',  'label' => '5% – Aliquota ridotta'],
        ['rate' => '4',  'label' => '4% – Aliquota minima'],
        ['rate' => '0',  'label' => '0% – Esente IVA'],

        // Nature SdI / FatturaPA 1.2
        ['rate' => 'N1',   'label' => 'N1 – Escluse ex art. 15'],
        ['rate' => 'N2.1', 'label' => 'N2.1 – Non soggette (artt. 7-7septies DPR 633/72)'],
        ['rate' => 'N2.2', 'label' => 'N2.2 – Non soggette (altri casi)'],
        ['rate' => 'N3.1', 'label' => 'N3.1 – Non imponibili (esportazioni)'],
        ['rate' => 'N3.2', 'label' => 'N3.2 – Non imponibili (cessioni intracomunitarie)'],
        ['rate' => 'N3.3', 'label' => 'N3.3 – Non imponibili (verso San Marino)'],
        ['rate' => 'N3.4', 'label' => 'N3.4 – Non imponibili (operazioni assimilate all\'esportazione)'],
        ['rate' => 'N3.5', 'label' => 'N3.5 – Non imponibili (dichiarazioni d\'intento)'],
        ['rate' => 'N3.6', 'label' => 'N3.6 – Non imponibili (altre operazioni)'],
        ['rate' => 'N4',   'label' => 'N4 – Esenti'],
        ['rate' => 'N5',   'label' => 'N5 – Regime del margine / IVA non esposta'],
        ['rate' => 'N6.1', 'label' => 'N6.1 – Reverse charge (rottami e materiali di recupero)'],
        ['rate' => 'N6.2', 'label' => 'N6.2 – Reverse charge (oro e argento)'],
        ['rate' => 'N6.3', 'label' => 'N6.3 – Reverse charge (subappalto edile)'],
        ['rate' => 'N6.4', 'label' => 'N6.4 – Reverse charge (cessione fabbricati)'],
        ['rate' => 'N6.5', 'label' => 'N6.5 – Reverse charge (telefoni cellulari)'],
        ['rate' => 'N6.6', 'label' => 'N6.6 – Reverse charge (prodotti elettronici)'],
        ['rate' => 'N6.7', 'label' => 'N6.7 – Reverse charge (edilizia e settori connessi)'],
        ['rate' => 'N6.8', 'label' => 'N6.8 – Reverse charge (settore energetico)'],
        ['rate' => 'N6.9', 'label' => 'N6.9 – Reverse charge (altri casi)'],
        ['rate' => 'N7',   'label' => 'N7 – IVA assolta in altro stato UE'],
    ],
];
