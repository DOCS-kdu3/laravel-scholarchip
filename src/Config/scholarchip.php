<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ScholarChip WSDL URL
    |--------------------------------------------------------------------------
    |
    | URL provided by ScholarChip to use in order to communicate with their
    | payment service.
    |
    */

    'wsdl_url' => env('SCHOLARCHIP_WSDL_URL', 'https://payservtst1.scholarchip.com/Webservices/000901/StoreFront.asmx?wsdl'),

    /*
    |--------------------------------------------------------------------------
    | ScholarChip User
    |--------------------------------------------------------------------------
    |
    | User provided by ScholarChip.
    |
    */

    'user' => env('SCHOLARCHIP_USER', ''),

     /*
    |--------------------------------------------------------------------------
    | ScholarChip Password
    |--------------------------------------------------------------------------
    |
    | Password provided by ScholarChip.
    |
    */

    'password' => env('SCHOLARCHIP_PASSWORD', ''),

     /*
    |--------------------------------------------------------------------------
    | ScholarChip General Ledger Account Number
    |--------------------------------------------------------------------------
    |
    | General ledger account number provided by ScholarChip. Also known as
    | sEAGL code.
    |
    */

    'gl' => env('SCHOLARCHIP_GL', ''),
];