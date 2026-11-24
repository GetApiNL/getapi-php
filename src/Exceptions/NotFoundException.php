<?php

declare(strict_types=1);

namespace GetApi\Exceptions;

/**
 * Er is niets gevonden voor deze aanvraag (HTTP 404), bijvoorbeeld een onbekend KVK-nummer of adres.
 *
 * Een zoekopdracht zonder resultaat telt voor de API als 404. Dit kost geen credits.
 */
class NotFoundException extends ApiException
{
}
