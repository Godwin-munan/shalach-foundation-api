<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Donation bank accounts
    |--------------------------------------------------------------------------
    |
    | Two account arrays (naira and dollar). Each array contains:
    |  - bank_name
    |  - account_name
    |  - account_number
    |  - account_type
    |
    | You can override these at runtime by passing $bankDetails to the Mailable.
    |
    */

    'naira' => [
        'bank_name'      => env('DONATIONS_NAIRA_BANK_NAME', 'UBA'),
        'account_name'   => env('DONATIONS_NAIRA_ACCOUNT_NAME', 'Shalach Empowerment Foundation'),
        'account_number' => env('DONATIONS_NAIRA_ACCOUNT_NUMBER', '1028497609'),
        'account_type'   => env('DONATIONS_NAIRA_ACCOUNT_TYPE', 'Naira Account'),
    ],

    'dollar' => [
        'bank_name'      => env('DONATIONS_DOLLAR_BANK_NAME', 'UBA'),
        'account_name'   => env('DONATIONS_DOLLAR_ACCOUNT_NAME', 'Shalach Empowerment Foundation'),
        'account_number' => env('DONATIONS_DOLLAR_ACCOUNT_NUMBER', '3004885585'),
        'account_type'   => env('DONATIONS_DOLLAR_ACCOUNT_TYPE', 'Dollar Account'),
    ],

    'narration' => env('DONATIONS_NARRATION', 'Please use the donation reference as narration'),

];
