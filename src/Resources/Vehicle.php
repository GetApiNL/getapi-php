<?php

declare(strict_types=1);

namespace GetApi\Resources;

use GetApi\Response;
use InvalidArgumentException;

/**
 * RDW-voertuiggegevens op kenteken.
 */
class Vehicle extends AbstractResource
{
    /**
     * Haal de voertuiggegevens bij een kenteken op (RDW Basisregistratie Voertuigen).
     *
     * Het antwoord bevat onder meer brandstof, emissieklasse, afmetingen en een vlag voor toegang tot milieuzones.
     *
     * @param string $licensePlate Het kenteken, met of zonder streepjes (bijvoorbeeld `AB-123-C`).
     *
     * @throws InvalidArgumentException Als het kenteken leeg is.
     */
    public function get(string $licensePlate): Response
    {
        $plate = strtoupper(preg_replace('/[\s-]+/', '', $licensePlate) ?? '');
        if ($plate === '') {
            throw new InvalidArgumentException('Het kenteken mag niet leeg zijn.');
        }

        return $this->transport->get('/v1/rdw/vehicle', ['license_plate' => $plate]);
    }
}
