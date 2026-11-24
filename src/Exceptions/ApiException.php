<?php

declare(strict_types=1);

namespace GetApi\Exceptions;

/**
 * De API gaf een foutstatus terug.
 *
 * Er zijn specifiekere varianten voor de veelvoorkomende gevallen: AuthException (401/403),
 * CreditsExhaustedException (402), NotFoundException (404), ValidationException (422),
 * RateLimitException (429) en UpstreamUnavailableException (503).
 */
class ApiException extends GetApiException
{
    /**
     * @param string               $message    Een leesbare melding in het Nederlands.
     * @param int                  $statusCode De HTTP-statuscode.
     * @param array<string, mixed> $data       De JSON-inhoud van het foutantwoord, indien aanwezig.
     */
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly array $data = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    /** De HTTP-statuscode van het antwoord. */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * De volledige JSON-inhoud van het foutantwoord.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /** De foutcode van de API (bijvoorbeeld `not_found` of `rate_limited`), of null als die ontbreekt. */
    public function getErrorCode(): ?string
    {
        $code = $this->data['error'] ?? null;

        return is_string($code) ? $code : null;
    }
}
