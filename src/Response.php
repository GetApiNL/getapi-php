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
    /** @var array<string, mixed> */
    private $data;

    /** @var int */
    private $statusCode;

    /** @var int|null */
    private $creditsRemaining;

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

    /**
     * De JSON-gegevens uit het antwoord, als array.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /** De HTTP-statuscode (200 bij succes). */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /** Aantal credits dat je nog hebt, uit de header `X-Credits-Remaining`, of null als die ontbreekt. */
    public function creditsRemaining(): ?int
    {
        return $this->creditsRemaining;
    }

    /**
     * Alleen-lezen toegang via `$response->data`, `$response->statusCode` en `$response->creditsRemaining`.
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'data' || $name === 'statusCode' || $name === 'creditsRemaining') {
            return $this->$name;
        }

        trigger_error('Onbekende eigenschap Response::$' . $name, E_USER_NOTICE);

        return null;
    }

    public function __isset(string $name): bool
    {
        return ($name === 'data' || $name === 'statusCode' || $name === 'creditsRemaining') && $this->$name !== null;
    }

    /**
     * @param mixed $value
     *
     * @throws \LogicException
     */
    public function __set(string $name, $value): void
    {
        throw new \LogicException('Response is alleen-lezen.');
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
