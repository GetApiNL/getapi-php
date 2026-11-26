<?php

declare(strict_types=1);

namespace GetApi\Resources;

use GetApi\Response;

/**
 * Pandgegevens: WOZ-waarde, energielabel en gecombineerde pandgegevens.
 *
 * Voor alle methodes geldt: splits het adres in losse delen. Zet de letter of toevoeging nooit in het huisnummer.
 * Voorbeeld: `Hooidrift 161C-03` wordt huisnummer 161, letter `C` en toevoeging `03`.
 */
class Property extends AbstractResource
{
    /**
     * WOZ-waarde van een pand, met de waarden van de laatste drie jaar.
     *
     * Let op: dit endpoint kost meer credits dan de meeste andere (10 per aanroep).
     *
     * @param string      $postcode            Nederlandse postcode, met of zonder spatie.
     * @param int         $houseNumber         Alleen het getal van het huisnummer.
     * @param string|null $houseLetter         Eén letter, bijvoorbeeld `C`.
     * @param string|null $houseNumberAddition De toevoeging zonder koppelteken, met voorloopnullen (bijvoorbeeld `03`).
     */
    public function wozValue(
        string $postcode,
        int $houseNumber,
        ?string $houseLetter = null,
        ?string $houseNumberAddition = null
    ): Response {
        return $this->transport->get('/v1/property/woz-waarde', [
            'postcode' => $this->requireNonEmpty($postcode, 'De postcode'),
        ] + $this->houseParameters($houseNumber, $houseLetter, $houseNumberAddition));
    }

    /**
     * Geregistreerd energielabel van een adres (bron: EP-Online van RVO).
     *
     * @param string      $postcode            Nederlandse postcode, met of zonder spatie.
     * @param int         $houseNumber         Alleen het getal van het huisnummer.
     * @param string|null $houseLetter         Eén letter, bijvoorbeeld `C`.
     * @param string|null $houseNumberAddition De toevoeging zonder koppelteken, met voorloopnullen (bijvoorbeeld `03`).
     */
    public function energyLabel(
        string $postcode,
        int $houseNumber,
        ?string $houseLetter = null,
        ?string $houseNumberAddition = null
    ): Response {
        return $this->transport->get('/v1/property/energy-label', [
            'zipcode' => $this->requireNonEmpty($postcode, 'De postcode'),
        ] + $this->houseParameters($houseNumber, $houseLetter, $houseNumberAddition));
    }

    /**
     * Gecombineerde pandgegevens: BAG, energielabel, WOZ-waarde, bestemmingsplan en omgevingsinformatie.
     *
     * @param string      $postcode            Nederlandse postcode, met of zonder spatie.
     * @param int         $houseNumber         Alleen het getal van het huisnummer.
     * @param string|null $houseLetter         Eén letter, bijvoorbeeld `C`.
     * @param string|null $houseNumberAddition De toevoeging zonder koppelteken, met voorloopnullen (bijvoorbeeld `03`).
     */
    public function details(
        string $postcode,
        int $houseNumber,
        ?string $houseLetter = null,
        ?string $houseNumberAddition = null
    ): Response {
        return $this->transport->get('/v1/property/pandgegevens', [
            'postcode' => $this->requireNonEmpty($postcode, 'De postcode'),
        ] + $this->houseParameters($houseNumber, $houseLetter, $houseNumberAddition));
    }
}
