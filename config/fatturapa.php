<?php

/**
 * Dati fiscali di Miticko necessari per generare l'XML FatturaPA.
 * Miticko è il "Cedente/Prestatore" per le fatture emesse verso i Partner
 * (quelle che addebitano commissioni di servizio, Miticko-side).
 *
 * I dati del Cedente delle fatture emesse al CLIENTE finale (dove il Partner
 * è il cedente) sono invece letti da PartnerBilling.
 *
 * Tutti i valori sono override-ables via .env per non committare informazioni fiscali reali.
 */
return [
    'miticko' => [
        'organization' => env('FPA_MITICKO_ORGANIZATION', 'Miticko S.r.l.'),
        'vat_number' => env('FPA_MITICKO_VAT_NUMBER'),
        'fiscal_code' => env('FPA_MITICKO_FISCAL_CODE'),
        'country_code' => env('FPA_MITICKO_COUNTRY_CODE', 'IT'),
        // Regime fiscale FatturaPA (es. RF01 ordinario, RF19 forfettario, …).
        'tax_regime' => env('FPA_MITICKO_TAX_REGIME', 'RF01'),

        'address' => [
            'street' => env('FPA_MITICKO_ADDRESS_STREET'),
            'street_number' => env('FPA_MITICKO_ADDRESS_NUMBER'),
            'zip' => env('FPA_MITICKO_ADDRESS_ZIP'),
            'city' => env('FPA_MITICKO_ADDRESS_CITY'),
            'province' => env('FPA_MITICKO_ADDRESS_PROVINCE'),
            'country_code' => env('FPA_MITICKO_ADDRESS_COUNTRY', 'IT'),
        ],

        // Codice destinatario SdI del trasmittente (di solito il proprio).
        'sender_vat_id' => env('FPA_MITICKO_SENDER_VAT_ID'),
    ],

    // Formato della trasmissione: FPR12 (privati/B2B/B2C) o FPA12 (Pubblica Amministrazione).
    // Per Miticko, le controparti sono privati → default FPR12.
    'transmission_format' => env('FPA_TRANSMISSION_FORMAT', 'FPR12'),
];
