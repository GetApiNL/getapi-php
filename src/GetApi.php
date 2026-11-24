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
final class GetApi
{
    /** Versie van deze client. Wordt meegestuurd in de User-Agent en in X-GetAPI-Client. */
    public const VERSION = '1.0.0';

    /** Standaard basis-URL van de GetAPI.nl API. */
    public const DEFAULT_BASE_URL = 'https://api.getapi.nl';

    private readonly Transport $transport;

    /** @var array<string, object> */
    private array $resources = [];

    /**
     * @param string                       $apiKey         Je GetAPI API-key (begint met `ga_live_`).
     * @param ClientInterface|null         $httpClient     Eigen PSR-18 client. Laat leeg om er automatisch een te zoeken.
     * @param RequestFactoryInterface|null $requestFactory Eigen PSR-17 request factory. Laat leeg voor automatisch zoeken.
     * @param StreamFactoryInterface|null  $streamFactory  Eigen PSR-17 stream factory. Laat leeg voor automatisch zoeken.
     * @param string                       $baseUrl        Basis-URL van de API. Alleen aanpassen voor tests.
     * @param string|null                  $clientName     Naam en versie van een wrapper, bijvoorbeeld `getapi-laravel/1.0.0`.
     * @param string|null                  $platform       Extra platforminfo voor de User-Agent, bijvoorbeeld `laravel/12`.
     *
     * @throws InvalidArgumentException Als de API-key leeg is.
     */
    public function __construct(
        string $apiKey,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?string $clientName = null,
        ?string $platform = null,
    ) {
        if (trim($apiKey) === '') {
            throw new InvalidArgumentException(
                'De API-key mag niet leeg zijn. Maak gratis een key aan op https://getapi.nl/register.'
            );
        }

        $php = 'php/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $userAgent = 'getapi-php/' . self::VERSION . ' (' . $php . ($platform !== null && $platform !== '' ? '; ' . $platform : '') . ')';

        $this->transport = new Transport(
            apiKey: trim($apiKey),
            baseUrl: $baseUrl,
            httpClient: $httpClient ?? Psr18ClientDiscovery::find(),
            requestFactory: $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            streamFactory: $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory(),
            userAgent: $userAgent,
            clientName: $clientName ?? 'getapi-php/' . self::VERSION,
        );
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
        return $this->resources[$class] ??= new $class($this->transport);
    }
}
