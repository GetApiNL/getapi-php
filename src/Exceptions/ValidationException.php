<?php

declare(strict_types=1);

namespace GetApi\Exceptions;

/**
 * De invoer is ongeldig (HTTP 422), bijvoorbeeld een postcode in het verkeerde formaat.
 *
 * Let op: bij de controles voor BTW-nummer, IBAN en e-mailadres is een ongeldige invoer geen fout.
 * Daar krijg je gewoon een antwoord met `valid: false`.
 */
class ValidationException extends ApiException
{
    /** @var array<string, mixed> */
    private $errors;

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $errors Per veld een lijst met meldingen.
     */
    public function __construct(string $message, int $statusCode, array $data = [], array $errors = [])
    {
        parent::__construct($message, $statusCode, $data);

        $this->errors = $errors;
    }

    /**
     * De meldingen per veld, bijvoorbeeld `['postcode' => ['De postcode is ongeldig.']]`.
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
