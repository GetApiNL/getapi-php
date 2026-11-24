<?php

declare(strict_types=1);

namespace GetApi\Resources;

use GetApi\Response;
use InvalidArgumentException;

/**
 * BAG-adressen: opzoeken op postcode en huisnummer, suggesties tijdens het typen en opzoeken op BAG-id.
 */
final class Address extends AbstractResource
{
    /**
     * Zoek een adres op met postcode en huisnummer (BAG).
     *
     * Splits het adres in losse delen. Zet de letter of toevoeging nooit in het huisnummer.
     * Voorbeeld: `Hooidrift 161C-03` wordt huisnummer 161, letter `C` en toevoeging `03`.
     *
     * @param string      $postcode            Nederlandse postcode, met of zonder spatie (bijvoorbeeld `3023KN`).
     * @param int         $houseNumber         Alleen het getal van het huisnummer.
     * @param string|null $houseLetter         Eén letter, bijvoorbeeld `C`.
     * @param string|null $houseNumberAddition De toevoeging zonder koppelteken, met voorloopnullen (bijvoorbeeld `03`).
     */
    public function lookup(
        string $postcode,
        int $houseNumber,
        ?string $houseLetter = null,
        ?string $houseNumberAddition = null,
    ): Response {
        return $this->transport->get('/v1/kadaster/bag/address', [
            'postcode' => $postcode,
        ] + $this->houseParameters($houseNumber, $houseLetter, $houseNumberAddition));
    }

    /**
     * Geef adressuggesties terwijl iemand typt.
     *
     * @param string   $query Zoektekst van 2 tot 100 tekens.
     * @param int|null $limit Maximaal aantal suggesties (1 tot 25). Laat leeg voor de standaardwaarde van de API.
     *
     * @throws InvalidArgumentException Als de zoektekst te kort of te lang is, of de limiet buiten 1 tot 25 valt.
     */
    public function suggest(string $query, ?int $limit = null): Response
    {
        $length = mb_strlen($query);
        if ($length < 2 || $length > 100) {
            throw new InvalidArgumentException('De zoektekst moet tussen 2 en 100 tekens lang zijn.');
        }
        if ($limit !== null && ($limit < 1 || $limit > 25)) {
            throw new InvalidArgumentException('De limiet moet tussen 1 en 25 liggen.');
        }

        return $this->transport->get('/v1/kadaster/bag/address-suggest', [
            'q' => $query,
            'limit' => $limit,
        ]);
    }

    /**
     * Zoek een adres op met het BAG-nummeraanduiding-id.
     *
     * @param string $id Het id van 16 cijfers.
     *
     * @throws InvalidArgumentException Als het id niet uit precies 16 cijfers bestaat.
     */
    public function byId(string $id): Response
    {
        if (preg_match('/^[0-9]{16}$/', $id) !== 1) {
            throw new InvalidArgumentException('Het BAG-id moet uit precies 16 cijfers bestaan.');
        }

        return $this->transport->get('/v1/kadaster/bag/id', ['id' => $id]);
    }

    /**
     * Zoek straat, plaats, gemeente en coördinaten op bij een postcode.
     *
     * @param string   $postcode Nederlandse postcode, met of zonder spatie.
     * @param int|null $number   Huisnummer voor een nauwkeuriger resultaat (optioneel).
     */
    public function postcode(string $postcode, ?int $number = null): Response
    {
        return $this->transport->get('/v1/lookup/postcode', [
            'postcode' => $postcode,
            'number' => $number,
        ]);
    }
}
