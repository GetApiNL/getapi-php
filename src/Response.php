<?php

declare(strict_types=1);

namespace GetApi;

/**
 * Antwoord van de GetAPI.nl API.
 *
 * De gegevens staan in `$data`. Daarnaast vind je hier het resterende aantal credits en de
 * ruwe headers, zodat je je verbruik kunt bijhouden zonder extra aanroep.
 */
final class Response
{
    /**
     * @param array<string, mixed>  $data             De JSON-gegevens uit het antwoord, als array.
     * @param int                   $statusCode       De HTTP-statuscode (200 bij succes).
     * @param int|null              $creditsRemaining Aantal credits dat je nog hebt, uit de header `X-Credits-Remaining`.
     * @param array<string, string> $headers          Alle headers, met kleine letters als sleutel.
     */
    public function __construct(
        public readonly array $data,
        public readonly int $statusCode = 200,
        public readonly ?int $creditsRemaining = null,
        private readonly array $headers = [],
    ) {
    }

    /** Geeft de waarde van een header terug (hoofdletters maken niet uit), of null als die ontbreekt. */
    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Haal één waarde uit de gegevens op, met een standaardwaarde als die ontbreekt.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Alle gegevens als array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
