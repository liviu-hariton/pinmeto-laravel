<?php

return [
    'app_id' => env('PINMETO_APP_ID', ''), // the PinMeTo `App ID`
    'app_secret' => env('PINMETO_APP_SECRET', ''), // the PinMeTo `App Secret`
    'account_id' => env('PINMETO_ACCOUNT_ID', ''), // the PinMeTo `Account ID`
    'mode' => env('PINMETO_MODE', 'test'), // the library working mode: `live` or `test` (defaults to `test`)
];
