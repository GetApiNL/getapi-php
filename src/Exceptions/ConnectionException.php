<?php

declare(strict_types=1);

namespace GetApi\Exceptions;

/**
 * De API kon niet worden bereikt (netwerkfout, time-out of DNS-probleem).
 *
 * De oorspronkelijke fout van de HTTP-client staat in `getPrevious()`.
 */
class ConnectionException extends GetApiException
{
}
