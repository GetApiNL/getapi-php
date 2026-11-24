<?php

declare(strict_types=1);

namespace GetApi\Resources;

use GetApi\Http\Transport;

/**
 * Basisklasse voor de resources. Bevat alleen de gedeelde verbinding.
 *
 * @internal
 */
abstract class AbstractResource
{
    /** @var Transport */
    protected $transport;

    public function __construct(Transport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Zet huisnummer, letter en toevoeging om naar de queryparameters van de API.
     *
     * @return array<string, scalar|null>
     */
    protected function houseParameters(
        int $houseNumber,
        ?string $houseLetter,
        ?string $houseNumberAddition
    ): array {
        return [
            'house_number' => $houseNumber,
            'house_letter' => $houseLetter,
            'house_number_addition' => $houseNumberAddition,
        ];
    }
}
