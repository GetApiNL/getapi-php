<?php

declare(strict_types=1);

namespace GetApi\Exceptions;

/**
 * De onderliggende bron (bijvoorbeeld KVK, VIES of EP-Online) is tijdelijk niet beschikbaar (HTTP 503).
 *
 * Probeer het later opnieuw. Dit kost geen credits.
 */
class UpstreamUnavailableException extends ApiException
{
}
