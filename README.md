# laravel-santimpay
 
Laravel package for integrating **SantimPay** payment gateway.
 
## Requirements
 
- **PHP**: 8.2+
- **Laravel / Illuminate**: 11.x or 12.x
 
## Installation
 
### Via Composer (Packagist)
 
```bash
composer require p4ndish/laravel-santimpay
```
 
### Via Composer (VCS / local development)
 
If the package is not published yet, you can install it from a Git repository:
 
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/<your-org-or-user>/laravel-santimpay"
    }
  ],
  "require": {
    "p4ndish/laravel-santimpay": "dev-main"
  }
}
```
 
Then run:
 
```bash
composer update
```
 
## Configuration
 
Publish the config file:
 
```bash
php artisan vendor:publish --tag=config
```
 
This will publish `config/santimpay.php`.
 
### Environment variables
 
Add the following to your `.env`:
 
```env
SANTIMPAY_API_URL=https://services.santimpay.com
SANTIMPAY_INITIATE_ENDPOINT=https://services.santimpay.com/api/v1/gateway/initiate-payment
SANTIMPAY_TRANSACTION_STATUS_ENDPOINT=https://services.santimpay.com/api/v1/gateway/fetch-transaction-status
 
SANTIMPAY_MERCHANT_ID=your_merchant_id
 
# IMPORTANT: this path is resolved using storage_path(...)
# Example below expects the key at: storage/app/santimpay/private.pem
SANTIMPAY_PRIVATE_KEY_PATH=app/santimpay/private.pem
 
SANTIMPAY_SUCCESS_URL=https://your-app.com/payments/success
SANTIMPAY_FAILURE_URL=https://your-app.com/payments/failure
SANTIMPAY_CANCEL_REDIRECT_URL=https://your-app.com/payments/cancel
SANTIMPAY_NOTIFY_URL=https://your-app.com/api/santimpay/notify
 
SANTIMPAY_RETRY_ATTEMPTS=3
SANTIMPAY_RETRY_SLEEP_MS=200
```
 
### Private key
 
The package loads the private key from the filesystem using:
 
- `storage_path(config('santimpay.private_key_path'))`
 
Example:
 
- Put your key at: `storage/app/santimpay/private.pem`
- Set: `SANTIMPAY_PRIVATE_KEY_PATH=app/santimpay/private.pem`
 
Make sure your key file is **not committed** to git.
 
## Usage
 
The package registers a singleton in the container as `santimpay` and also provides a facade alias `SantimPay`.
 
### Generate a transaction ID
 
```php
use P4ndish\SantimPay\Facades\SantimPay;
 
$merchantTxnId = SantimPay::generateMerchantTxnId();
```
 
### Initiate a payment
 
`initiatePayment(...)` returns an array like:
 
- `status_code` (int)
- `url` (string|null) payment redirect URL
- `body` (array) full response JSON
 
Example controller action:
 
```php
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use P4ndish\SantimPay\Exception\SantimPayException;
use P4ndish\SantimPay\Facades\SantimPay;
 
public function pay(Request $request): RedirectResponse
{
    $merchantTxnId = SantimPay::generateMerchantTxnId();
 
    try {
        $res = SantimPay::initiatePayment(
            merchantTxnId: $merchantTxnId,
            amount: 100,
            reason: 'Order #123',
            phoneNumber: $request->input('phone')
        );
    } catch (SantimPayException $e) {
        abort($e->getStatus(), $e->getMessage());
    }
 
    if (!($res['url'] ?? null)) {
        abort(500, 'SantimPay did not return a redirect URL.');
    }
 
    return redirect()->away($res['url']);
}
```
 
### Check transaction status
 
```php
use P4ndish\SantimPay\Facades\SantimPay;
 
$status = SantimPay::checkTransactionStatus($merchantTxnId);
// $status is the decoded JSON response array
```
 
## Handling redirects and notifications
 
SantimPay can redirect the customer back to your app (success/failure/cancel), and it can also call your `notifyUrl`.
 
Recommended approach:
 
- Create routes/controllers for:
  - `SANTIMPAY_SUCCESS_URL`
  - `SANTIMPAY_FAILURE_URL`
  - `SANTIMPAY_CANCEL_REDIRECT_URL`
  - `SANTIMPAY_NOTIFY_URL`
- In those handlers, verify the transaction with `checkTransactionStatus($merchantTxnId)` before marking an order as paid.
 
## Error handling
 
Most failures during API calls are thrown as `P4ndish\SantimPay\Exception\SantimPayException`.
 
```php
use P4ndish\SantimPay\Exception\SantimPayException;
 
try {
    // ... call initiatePayment / checkTransactionStatus
} catch (SantimPayException $e) {
    report($e);
    return response()->json([
        'message' => $e->getMessage(),
    ], $e->getStatus());
}
```
 
## Security notes
 
- Never commit your private key.
- Prefer storing the key under `storage/` and restricting file permissions.
- Always validate/verify transactions server-side using `checkTransactionStatus(...)`.
 
## Contributing
 
Contributions are welcome.
 
- Fork the repo
- Create a feature branch
- Open a PR
 
## License
 
MIT
