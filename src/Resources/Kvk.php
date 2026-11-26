<?php

declare(strict_types=1);

namespace GetApi\Resources;

use GetApi\Response;
use InvalidArgumentException;

/**
 * KVK-handelsregister: bedrijven zoeken, details ophalen en KVK-nummers controleren.
 */
class Kvk extends AbstractResource
{
    private const ALLOWED_SIZES = [5, 10, 25, 50, 75];

    /**
     * Zoek bedrijven op handelsnaam, op KVK-nummer, of op beide.
     *
     * Een zoekopdracht zonder resultaat geeft een NotFoundException. Dat kost geen credits.
     *
     * @param string|null $naam       Handelsnaam om op te zoeken.
     * @param string|null $kvkNummer  KVK-nummer. De API controleert de opmaak (8 cijfers).
     * @param int         $page       Paginanummer, beginnend bij 1.
     * @param int         $size       Resultaten per pagina: 5, 10, 25, 50 of 75.
     *
     * @throws InvalidArgumentException Als zowel naam als KVK-nummer ontbreekt, of als de paginagrootte niet is toegestaan.
     */
    public function search(?string $naam = null, ?string $kvkNummer = null, int $page = 1, int $size = 25): Response
    {
        if (($naam === null || trim($naam) === '') && ($kvkNummer === null || trim($kvkNummer) === '')) {
            throw new InvalidArgumentException('Geef een naam, een KVK-nummer of allebei op om te zoeken.');
        }
        if (!in_array($size, self::ALLOWED_SIZES, true)) {
            throw new InvalidArgumentException('De paginagrootte moet 5, 10, 25, 50 of 75 zijn.');
        }
        if ($page < 1) {
            throw new InvalidArgumentException('Het paginanummer moet 1 of hoger zijn.');
        }

        return $this->transport->get('/v1/kvk/search', [
            'naam' => $naam,
            'kvkNummer' => $kvkNummer,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Haal de gegevens van een bedrijf op met het KVK-nummer.
     *
     * @param string $kvkNummer KVK-nummer. De API controleert de opmaak (8 cijfers).
     */
    public function get(string $kvkNummer): Response
    {
        $kvkNummer = $this->requireNonEmpty($kvkNummer, 'Het KVK-nummer');

        return $this->transport->get('/v1/kvk/' . rawurlencode($kvkNummer));
    }

    /**
     * Controleer of een KVK-nummer bestaat en actief is.
     *
     * Een ongeldig nummer geeft geen fout, maar een antwoord met `valid: false`.
     *
     * @param string $kvkNummer KVK-nummer. De API controleert de opmaak (8 cijfers).
     */
    public function validate(string $kvkNummer): Response
    {
        return $this->transport->post('/v1/kvk/validate', ['kvkNummer' => $this->requireNonEmpty($kvkNummer, 'Het KVK-nummer')], true);
    }
}
