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
    | User provided by Scholarchip.
    |
    */

    'user' => env('SCHOLARCHIP_USER', ''),

     /*
    |--------------------------------------------------------------------------
    | ScholarChip Password
    |--------------------------------------------------------------------------
    |
    | Password provided by Scholarchip.
    |
    */

    'password' => env('SCHOLARCHIP_PASSWORD', ''),

     /*
    |--------------------------------------------------------------------------
    | ScholarChip General Ledger Account Number
    |--------------------------------------------------------------------------
    |
    | General ledger account number provided by Scholarchip. Also known as
    | sEAGL code.
    |
    */

    'gl' => env('SCHOLARCHIP_GL', ''),

     /*
    |--------------------------------------------------------------------------
    | ScholarChip Callback Relative URL
    |--------------------------------------------------------------------------
    |
    | The URL should be a URL relative to the application. This setting is
    | optional and can be left blank.
    |
    | If provided, the callback URL is the address which the user will be sent 
    | to once they have finished entering payment information at the 
    | ScholarChip storefront. It will be some page on your site which checks 
    | to see that the order has been processed appropriately and makes 
    | whatever local database changes are required to update the user's order.
    |
    | If not provided, the callback URL will need to be specified upon
    | payment initiation call.
    |
    */

    'callback_url' => env('SCHOLARCHIP_CALLBACK_URL', ''),

     /*
    |--------------------------------------------------------------------------
    | ScholarChip Order Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix to be added to order number for additional identification 
    | purposes. This setting is optional and can be left blank.
    |
    */

    'order_prefix' => env('SCHOLARCHIP_ORDER_PREFIX', ''),

];