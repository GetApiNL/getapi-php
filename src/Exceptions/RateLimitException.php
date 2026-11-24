<?php

declare(strict_types=1);

namespace GetApi\Exceptions;

/**
 * Je hebt de limiet per minuut voor deze API-key bereikt (HTTP 429).
 *
 * Wacht het aantal seconden uit `getRetryAfter()` en probeer het daarna opnieuw.
 */
class RateLimitException extends ApiException
{
    /** @var int|null */
    private $retryAfter;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(string $message, int $statusCode, array $data = [], ?int $retryAfter = null)
    {
        parent::__construct($message, $statusCode, $data);

        $this->retryAfter = $retryAfter;
    }

    /** Aantal seconden dat je moet wachten, of null als de API dat niet aangaf. */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
