<?php
namespace P4ndish\SantimPay\Facades;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null generatePaymentUrl(array $data)
 * @method static array|null getTransactionById(string $transactionId)
 *
 * @see \P4ndish\SantimPay\SantimPay
 */
class SantimPay extends Facade
{
    protected static function getFacadeAccessor(): string { return 'santimpay'; }
}