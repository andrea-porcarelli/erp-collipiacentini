<?php

/**
 * Aliquote IVA standard italiane (DPR 633/1972).
 * Fonte unica consumata dal componente <x-vat-select>.
 *
 * Il value salvato nel DB è il campo `rate` (decimal).
 * Il campo `label` è quello mostrato nella select.
 */
return [
    'rates' => [
        ['rate' => 22, 'label' => '22% – Aliquota ordinaria'],
        ['rate' => 10, 'label' => '10% – Aliquota ridotta'],
        ['rate' => 5,  'label' => '5% – Aliquota ridotta'],
        ['rate' => 4,  'label' => '4% – Aliquota minima'],
        ['rate' => 0,  'label' => '0% – Esente / Non imponibile / Fuori campo'],
    ],
];
