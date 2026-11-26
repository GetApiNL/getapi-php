<?php

declare(strict_types=1);

namespace GetApi;

use GetApi\Http\Transport;
use GetApi\Resources\Address;
use GetApi\Resources\Kvk;
use GetApi\Resources\Lookup;
use GetApi\Resources\Property;
use GetApi\Resources\Sbi;
use GetApi\Resources\Validation;
use GetApi\Resources\Vehicle;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Hoofdingang van de GetAPI.nl PHP-client.
 *
 * Voorbeeld:
 *
 *     $getapi = new GetApi\GetApi('ga_live_...');
 *     $bedrijf = $getapi->kvk()->get('53743792');
 *     echo $bedrijf->data['naam'], ' - credits over: ', $bedrijf->creditsRemaining;
 *
 * De client gebruikt PSR-18, dus elke HTTP-client werkt (Guzzle, Symfony HttpClient, enzovoort).
 * Geef je eigen client mee in de constructor, of laat de client er zelf een zoeken.
 */
class GetApi
{
    public const VERSION = '1.1.0';

    public const DEFAULT_BASE_URL = 'https://api.getapi.nl';

    /** @var Transport */
    private $transport;

    /** @var array<string, object> */
    private $resources = [];

    /**
     * @param string                       $apiKey         Je GetAPI API-key (begint met `ga_live_`).
     * @param ClientInterface|null         $httpClient     Eigen PSR-18 client. Laat leeg om er automatisch een te zoeken.
     * @param RequestFactoryInterface|null $requestFactory Eigen PSR-17 request factory. Laat leeg voor automatisch zoeken.
     * @param StreamFactoryInterface|null  $streamFactory  Eigen PSR-17 stream factory. Laat leeg voor automatisch zoeken.
     * @param string                       $baseUrl        Basis-URL van de API. Moet https zijn (http mag alleen voor localhost). Alleen aanpassen voor tests.
     * @param string|null                  $clientName     Naam en versie van een wrapper, bijvoorbeeld `mijn-wrapper/1.0.0`.
     * @param string|null                  $platform       Extra platforminfo voor de User-Agent, bijvoorbeeld `mijn-app/2.0`.
     *
     * @throws InvalidArgumentException Als de API-key leeg is of de basis-URL geen https gebruikt.
     */
    public function __construct(
        string $apiKey,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?string $clientName = null,
        ?string $platform = null
    ) {
        if (trim($apiKey) === '') {
            throw new InvalidArgumentException(
                'De API-key mag niet leeg zijn. Maak gratis een key aan op https://getapi.nl/register.'
            );
        }

        self::assertSecureBaseUrl($baseUrl);

        $php = 'php/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $userAgent = 'getapi-php/' . self::VERSION . ' (' . $php . ($platform !== null && $platform !== '' ? '; ' . $platform : '') . ')';

        $this->transport = new Transport(
            trim($apiKey),
            $baseUrl,
            $httpClient ?? Psr18ClientDiscovery::find(),
            $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory(),
            $userAgent,
            $clientName ?? 'getapi-php/' . self::VERSION
        );
    }

    private static function assertSecureBaseUrl(string $baseUrl): void
    {
        $parts = parse_url($baseUrl);
        $scheme = is_array($parts) && isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $host = is_array($parts) && isset($parts['host']) ? strtolower(trim($parts['host'], '[]')) : '';

        if ($scheme === 'https' && $host !== '') {
            return;
        }
        if ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return;
        }

        throw new InvalidArgumentException('De basis-URL moet met https:// beginnen, anders gaat je API-key onversleuteld over de lijn.');
    }

    /** KVK-handelsregister: bedrijven zoeken, details ophalen en KVK-nummers controleren. */
    public function kvk(): Kvk
    {
        return $this->resource(Kvk::class);
    }

    /** BAG-adressen: opzoeken op postcode en huisnummer, suggesties en opzoeken op BAG-id. */
    public function address(): Address
    {
        return $this->resource(Address::class);
    }

    /** Pandgegevens: WOZ-waarde, energielabel en gecombineerde pandgegevens. */
    public function property(): Property
    {
        return $this->resource(Property::class);
    }

    /** Controles: BTW-nummer (VIES), IBAN, e-mailadres en IP-blacklists. */
    public function validation(): Validation
    {
        return $this->resource(Validation::class);
    }

    /** Opzoeken: postcode, IP-adres en DNS. */
    public function lookup(): Lookup
    {
        return $this->resource(Lookup::class);
    }

    /** RDW-voertuiggegevens op kenteken. */
    public function vehicle(): Vehicle
    {
        return $this->resource(Vehicle::class);
    }

    /** CBS SBI-codes (bedrijfsindeling): index, zoeken en opzoeken op code. */
    public function sbi(): Sbi
    {
        return $this->resource(Sbi::class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function resource(string $class): object
    {
        if (!isset($this->resources[$class])) {
            $this->resources[$class] = new $class($this->transport);
        }

        return $this->resources[$class];
    }
}
