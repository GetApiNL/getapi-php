<?php

declare(strict_types=1);

namespace GetApi\Exceptions;

/**
 * Je credits zijn op (HTTP 402).
 *
 * Gebruik `getTopupUrl()` om je gebruiker naar de pagina te sturen waar het saldo kan worden opgewaardeerd.
 */
class CreditsExhaustedException extends ApiException
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        string $message,
        int $statusCode,
        array $data = [],
        private readonly ?string $topupUrl = null,
    ) {
        parent::__construct($message, $statusCode, $data);
    }

    /** Link om het saldo op te waarderen, of null als de API die niet meegaf. */
    public function getTopupUrl(): ?string
    {
        return $this->topupUrl;
    }
}
