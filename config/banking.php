<?php

return [

    'gocardless' => [
        'secret_id' => env('GOCARDLESS_SECRET_ID'),
        'secret_key' => env('GOCARDLESS_SECRET_KEY'),
        'base_url' => env('GOCARDLESS_BASE_URL', 'https://bankaccountdata.gocardless.com/api/v2'),
        'redirect_uri' => env('GOCARDLESS_REDIRECT_URI'),
        'default_institution_id' => env('GOCARDLESS_TIDE_INSTITUTION_ID', 'TIDE_TIDEGB22'),
        'agreement_days' => (int) env('GOCARDLESS_AGREEMENT_DAYS', 90),
    ],

    'expenses' => [
        'receipt_disk' => env('EXPENSE_RECEIPT_DISK', 'local'),
    ],

];
