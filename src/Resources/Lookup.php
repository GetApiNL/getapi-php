<?php

declare(strict_types=1);

namespace GetApi\Resources;

use GetApi\Response;
use InvalidArgumentException;

/**
 * Opzoeken: IP-adres en DNS. Voor het opzoeken van een postcode gebruik je `$getapi->address()->postcode()`.
 */
final class Lookup extends AbstractResource
{
    /** Toegestane recordtypes voor een DNS-opzoeking. */
    private const DNS_TYPES = ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'TXT', 'SOA', 'PTR', 'SRV', 'ALL'];

    /**
     * Zoek de aanbieder (ISP) en locatie van een IP-adres op.
     *
     * @param string $ip Het IP-adres.
     */
    public function ip(string $ip): Response
    {
        return $this->transport->get('/v1/lookup/ip', ['ip' => $ip]);
    }

    /**
     * Zoek de nameservers van een domein op.
     *
     * @param string $domain Domeinnaam, bijvoorbeeld `getapi.nl` (maximaal 253 tekens).
     */
    public function dnsNameservers(string $domain): Response
    {
        return $this->transport->get('/v1/lookup/dns/nameservers', ['domain' => $domain]);
    }

    /**
     * Zoek DNS-records van een domein op.
     *
     * @param string      $domain Domeinnaam, bijvoorbeeld `getapi.nl` (maximaal 253 tekens).
     * @param string|null $type   Recordtype: A, AAAA, CNAME, MX, NS, TXT, SOA, PTR, SRV of ALL. Laat leeg voor de standaardwaarde van de API.
     *
     * @throws InvalidArgumentException Als het recordtype niet wordt ondersteund.
     */
    public function dnsRecords(string $domain, ?string $type = null): Response
    {
        if ($type !== null && !in_array(strtoupper($type), self::DNS_TYPES, true)) {
            throw new InvalidArgumentException('Onbekend recordtype. Kies uit: ' . implode(', ', self::DNS_TYPES) . '.');
        }

        return $this->transport->get('/v1/lookup/dns/records', [
            'domain' => $domain,
            'type' => $type !== null ? strtoupper($type) : null,
        ]);
    }
}
