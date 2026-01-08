<?php

namespace P4ndish\SantimPay\Exception;

class SantimPayException extends \Exception
{
    // Custom exception for SantimPay errors
    protected $status;

    public function __construct(string $message, int $status = 200)
    {
        parent::__construct($message);
        $this->status = $status;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
