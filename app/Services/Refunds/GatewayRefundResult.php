<?php

namespace App\Services\Refunds;

class GatewayRefundResult
{
    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly ?string $gatewayRefundId = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function skipped(): self
    {
        return new self(self::STATUS_SKIPPED);
    }

    public static function success(?string $gatewayRefundId = null): self
    {
        return new self(self::STATUS_SUCCESS, $gatewayRefundId);
    }

    public static function failed(string $errorMessage): self
    {
        return new self(self::STATUS_FAILED, null, $errorMessage);
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
