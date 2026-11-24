<?php

declare(strict_types=1);

namespace GetApi\Exceptions;

/**
 * De API-key ontbreekt, is ongeldig (401) of heeft geen toegang tot dit endpoint (403).
 */
class AuthException extends ApiException
{
}
