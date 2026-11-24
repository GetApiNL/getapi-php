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
     * De JSON-gegevens uit het antwoord, als array. Alleen lezen.
     *
     * @var array<string, mixed>
     */
    public $data;

    /**
     * De HTTP-statuscode (200 bij succes). Alleen lezen.
     *
     * @var int
     */
    public $statusCode;

    /**
     * Aantal credits dat je nog hebt, uit de header `X-Credits-Remaining`. Alleen lezen.
     *
     * @var int|null
     */
    public $creditsRemaining;

    /** @var array<string, string> */
    private $headers;

    /**
     * @param array<string, mixed>  $data             De JSON-gegevens uit het antwoord, als array.
     * @param int                   $statusCode       De HTTP-statuscode (200 bij succes).
     * @param int|null              $creditsRemaining Aantal credits dat je nog hebt, uit de header `X-Credits-Remaining`.
     * @param array<string, string> $headers          Alle headers, met kleine letters als sleutel.
     */
    public function __construct(array $data, int $statusCode = 200, ?int $creditsRemaining = null, array $headers = [])
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->creditsRemaining = $creditsRemaining;
        $this->headers = $headers;
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
    public function get(string $key, $default = null)
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
