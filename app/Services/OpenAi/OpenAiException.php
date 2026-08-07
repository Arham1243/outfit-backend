<?php

namespace App\Services\OpenAi;

class OpenAiException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?string $endpoint = null,
        private readonly ?string $errorType = null,
        private readonly int $httpStatus = 0,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(int $status, mixed $payload, ?string $endpoint = null): self
    {
        $error = is_array($payload) ? ($payload['error'] ?? null) : null;
        $errorType = is_array($error) ? ($error['type'] ?? $error['code'] ?? null) : null;
        $errorMessage = is_array($error)
            ? ($error['message'] ?? null)
            : (is_string($payload) ? $payload : null);

        $message = $errorMessage
            ?? (is_array($payload) ? ($payload['message'] ?? null) : null)
            ?? sprintf('OpenAI API request failed with status %d.', $status);

        return new self(
            $message,
            $endpoint,
            is_string($errorType) ? $errorType : null,
            $status
        );
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
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

        return in_array($this->errorType, [
            'rate_limit_exceeded',
            'server_error',
            'service_unavailable',
        ], true);
    }
}
