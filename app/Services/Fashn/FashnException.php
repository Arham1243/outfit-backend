<?php

namespace App\Services\Fashn;

class FashnException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?string $modelName = null,
        private readonly ?string $errorName = null,
        private readonly int $httpStatus = 0,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(int $status, mixed $payload, ?string $modelName = null): self
    {
        $error = is_array($payload) ? ($payload['error'] ?? null) : null;
        $errorName = is_array($error) ? ($error['name'] ?? null) : null;
        $errorMessage = is_array($error)
            ? ($error['message'] ?? null)
            : (is_string($payload) ? $payload : null);

        $message = $errorMessage
            ?? (is_array($payload) ? ($payload['message'] ?? null) : null)
            ?? sprintf('FASHN API request failed with status %d.', $status);

        return new self($message, $modelName, is_string($errorName) ? $errorName : null, $status);
    }

    public function getModelName(): ?string
    {
        return $this->modelName;
    }

    public function getErrorName(): ?string
    {
        return $this->errorName;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function isRetryable(): bool
    {
        if ($this->httpStatus === 429 || $this->httpStatus >= 500) {
            return true;
        }

        return in_array($this->errorName, [
            'RateLimitError',
            'ServiceUnavailableError',
            'InternalServerError',
        ], true);
    }
}
