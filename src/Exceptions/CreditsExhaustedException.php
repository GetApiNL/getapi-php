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
    /** @var string|null */
    private $topupUrl;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(string $message, int $statusCode, array $data = [], ?string $topupUrl = null)
    {
        parent::__construct($message, $statusCode, $data);

        $this->topupUrl = $topupUrl;
    }

    /** Link om het saldo op te waarderen, of null als de API die niet meegaf. */
    public function getTopupUrl(): ?string
    {
        return $this->topupUrl;
    }
}
