<?php

declare(strict_types=1);

namespace GetApi\Exceptions;

use RuntimeException;

/**
 * Basisklasse van alle fouten uit deze client.
 *
 * Vang deze klasse op als je alle GetAPI-fouten op dezelfde manier wilt afhandelen.
 */
class GetApiException extends RuntimeException
{
}
