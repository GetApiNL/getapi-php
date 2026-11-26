<?php

declare(strict_types=1);

namespace GetApi\Resources;

use GetApi\Response;
use InvalidArgumentException;

/**
 * CBS SBI-codes: de standaard bedrijfsindeling van het Centraal Bureau voor de Statistiek.
 */
class Sbi extends AbstractResource
{
    /**
     * Haal de SBI-codes op, optioneel per niveau of onder een bovenliggende code.
     *
     * @param int|null    $level      Niveau van 1 tot 5 (optioneel).
     * @param string|null $parentCode Bovenliggende code, maximaal 12 tekens (optioneel).
     *
     * @throws InvalidArgumentException Als het niveau buiten 1 tot 5 valt.
     */
    public function index(?int $level = null, ?string $parentCode = null): Response
    {
        if ($level !== null && ($level < 1 || $level > 5)) {
            throw new InvalidArgumentException('Het niveau moet tussen 1 en 5 liggen.');
        }

        return $this->transport->get('/v1/cbs/sbi', [
            'level' => $level,
            'parent_code' => $parentCode,
        ]);
    }

    /**
     * Zoek SBI-codes op omschrijving.
     *
     * @param string   $query Zoektekst van 2 tot 100 tekens.
     * @param int|null $limit Maximaal aantal resultaten (1 tot 100). Laat leeg voor de standaardwaarde van de API.
     *
     * @throws InvalidArgumentException Als de zoektekst te kort of te lang is, of de limiet buiten 1 tot 100 valt.
     */
    public function search(string $query, ?int $limit = null): Response
    {
        $length = mb_strlen($query);
        if ($length < 2 || $length > 100) {
            throw new InvalidArgumentException('De zoektekst moet tussen 2 en 100 tekens lang zijn.');
        }
        if ($limit !== null && ($limit < 1 || $limit > 100)) {
            throw new InvalidArgumentException('De limiet moet tussen 1 en 100 liggen.');
        }

        return $this->transport->get('/v1/cbs/sbi/search', ['q' => $query, 'limit' => $limit]);
    }

    /**
     * Zoek één SBI-code op.
     *
     * @param string $code De SBI-code, bijvoorbeeld `62`.
     */
    public function get(string $code): Response
    {
        $code = trim($code);
        if ($code === '') {
            throw new InvalidArgumentException('De SBI-code mag niet leeg zijn.');
        }

        return $this->transport->get('/v1/cbs/sbi/' . rawurlencode($code));
    }
}
