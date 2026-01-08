<?php
return [
    'api_url' => env('SANTIMPAY_API_URL', 'https://services.santimpay.com'),
    'initiate_endpoint' => env('SANTIMPAY_INITIATE_ENDPOINT', 'https://services.santimpay.com/api/v1/gateway/initiate-payment'),
    'transaction_status_endpoint' => env('SANTIMPAY_TRANSACTION_STATUS_ENDPOINT', 'https://services.santimpay.com/api/v1/gateway/fetch-transaction-status'),
    'merchant_id' => env('SANTIMPAY_MERCHANT_ID', ''),
    'private_key_path' => env('SANTIMPAY_PRIVATE_KEY_PATH', ''),
    'success_url' => env('SANTIMPAY_SUCCESS_URL', ''),
    'failure_url' => env('SANTIMPAY_FAILURE_URL', ''),
    'notify_url' => env('SANTIMPAY_NOTIFY_URL', ''),
    'cancel_redirect_url' => env('SANTIMPAY_CANCEL_REDIRECT_URL', ''),
    'retry_attempts' => env('SANTIMPAY_RETRY_ATTEMPTS', 3),
    'retry_sleep_ms' => env('SANTIMPAY_RETRY_SLEEP_MS', 200),
];