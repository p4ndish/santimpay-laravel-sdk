<?php
namespace P4ndish\SantimPay;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use P4ndish\SantimPay\Exception\SantimPayException;

class SantimPay
{
    protected string $apiUrl;
    protected string $merchantId;
    protected string $privateKey;
    protected string $success_url;
    protected string $failure_url;
    protected string $notify_url;
    protected string $cancelRedirectUrl;
    protected int $retryAttempts;
    protected int $retrySleepMs;
    protected string $initiate_endpoint;
    protected string $transaction_status_endpoint;

    public function __construct()
    {
        $this->apiUrl = Config::get('santimpay.api_url');
        $this->merchantId = Config::get('santimpay.merchant_id');
        $privateKeyPath = Config::get('santimpay.private_key_path');
        $this->privateKey = $this->loadPrivateKey($privateKeyPath);
        $this->success_url = Config::get('santimpay.success_url');
        $this->failure_url = Config::get('santimpay.failure_url');
        $this->notify_url = Config::get('santimpay.notify_url');
        $this->cancelRedirectUrl = Config::get('santimpay.cancel_redirect_url');
        $this->retryAttempts = (int) Config::get('santimpay.retry_attempts', 3);
        $this->retrySleepMs = (int) Config::get('santimpay.retry_sleep_ms', 200);
        $this->initiate_endpoint = Config::get('santimpay.initiate_endpoint');
        $this->transaction_status_endpoint = Config::get('santimpay.transaction_status_endpoint');
    }

    public function generateMerchantTxnId(): string
    {
        return (string) Str::uuid();
    }


    protected function signToken(array $payload): string
    {
        return JWT::encode($payload, $this->privateKey, 'ES256');
    }

    protected function loadPrivateKey(?string $path): string
    {
        if (!$path) {
            throw new \RuntimeException('SantimPay private key path is not configured.');
        }

        $fullPath = storage_path($path);

        if (!file_exists($fullPath)) {
            throw new \RuntimeException("SantimPay private key not found at: {$fullPath}");
        }

        return file_get_contents($fullPath);
    }




    public function generateSignedToken($amount, $paymentReason)
    {
        $time = time();
        $data = array(
            'amount' => $amount,
            'paymentReason' => $paymentReason,
            'merchantId' => $this->merchantId,
            'generated' => $time
        );

        $jwt = JWT::encode($data, $this->privateKey, 'ES256');

        return $jwt;
    }

    public function initiatePayment(
        string $merchantTxnId,
        int $amount,
        string $reason,
        ?string $phoneNumber = null
    ): array {
        try {
            $signedToken = $this->signToken([
                'amount' => $amount,
                'paymentReason' => $reason,
                'merchantId' => $this->merchantId,
                'generated' => time(),
            ]);

            $payload = [
                'id' => $merchantTxnId,
                'amount' => $amount,
                'reason' => $reason,
                'merchantId' => $this->merchantId,
                'signedToken' => $signedToken,
                'successRedirectUrl' => $this->success_url,
                'failureRedirectUrl' => $this->failure_url,
                'cancelRedirectUrl' => $this->cancelRedirectUrl,
                'notifyUrl' => $this->notify_url,
            ];

            if ($phoneNumber) {
                $payload['phoneNumber'] = $phoneNumber;
            }

            $response = Http::retry($this->retryAttempts, $this->retrySleepMs)
                ->acceptJson()
                ->post($this->initiate_endpoint, $payload);

            $responseData = $response->json();

            return [
                'status_code' => $response->status(),
                'url' => $responseData['url'] ?? null,
                'body' => $responseData
            ];

        } catch (RequestException $e) {
            $response = $e->response;
            $status = $response?->status() ?? 0;
            $message = $response?->json('message')
                ?? ($response?->body() ?: $e->getMessage());

            throw new SantimPayException(
                $message,
                $status
            );
        } catch (\Throwable $e) {
            throw new SantimPayException(
                $e->getMessage(),
                0,
            );
        }
    }


    public function generateSignedTokenForGetTransaction($id)
    {
        $time = time();
        $data = array(
            'id' => $id,
            'merId' => $this->merchantId,
            'generated' => $time
        );
        $jwt = JWT::encode($data, $this->privateKey, 'ES256');

        return $jwt;
    }

    public function checkTransactionStatus(string $id)
    {
        try {
            $token = $this->generateSignedTokenForGetTransaction($id);

            return Http::retry($this->retryAttempts, $this->retrySleepMs)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=UTF-8',
                ])
                ->post($this->transaction_status_endpoint, [
                    'Id' => $id,
                    'MerchantId' => $this->merchantId,
                    'SignedToken' => $token,
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            $response = $e->response;
            $status = $response?->status() ?? 0;
            $message = $response?->json('message')
                ?? ($response?->body() ?: $e->getMessage());

            throw new SantimPayException(
                $message,
                (int) $status
            );
        } catch (\Throwable $e) {
            throw new SantimPayException(
                $e->getMessage(),
                0,
            );
        }
    }
}