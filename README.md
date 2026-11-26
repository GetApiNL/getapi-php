# GetAPI PHP-client

[![Packagist](https://img.shields.io/packagist/v/getapinl/getapi-php.svg)](https://packagist.org/packages/getapinl/getapi-php)
[![Tests](https://github.com/GetApiNL/getapi-php/actions/workflows/tests.yml/badge.svg)](https://github.com/GetApiNL/getapi-php/actions/workflows/tests.yml)
[![Licentie](https://img.shields.io/packagist/l/getapinl/getapi-php.svg)](LICENSE)

De officiële PHP-client voor [GetAPI.nl](https://getapi.nl/?utm_source=packagist&utm_medium=sdk&utm_campaign=php-readme): Nederlandse bedrijfs-, adres-, pand- en controlegegevens via één API-key. KVK, BAG, BTW (VIES), WOZ, energielabel, RDW en meer, zonder zelf een KVK-contract, certificaat of aparte koppelingen te regelen.

GetAPI verzamelt en publiceert de data zelf, onder een eigen domein, API en account. Eén key is genoeg.

## Snel starten

```bash
composer require getapinl/getapi-php
```

```php
use GetApi\GetApi;

$getapi = new GetApi('ga_live_...');

$bedrijf = $getapi->kvk()->get('53743792');

echo $bedrijf->data['naam'];
```

[Maak gratis een API-key aan](https://getapi.nl/register?utm_source=packagist&utm_medium=sdk&utm_campaign=php-readme&utm_content=quickstart) (250 gratis credits per maand).

## Wat kun je ermee?

| Resource | Methodes |
| --- | --- |
| `$getapi->kvk()` | `search()`, `get()`, `validate()` |
| `$getapi->address()` | `lookup()`, `suggest()`, `byId()`, `postcode()` |
| `$getapi->property()` | `wozValue()`, `energyLabel()`, `details()` |
| `$getapi->validation()` | `vat()`, `iban()`, `email()`, `emailBatch()`, `disposableEmail()`, `dnsbl()` |
| `$getapi->lookup()` | `ip()`, `dnsNameservers()`, `dnsRecords()` |
| `$getapi->vehicle()` | `get()` (RDW-kenteken) |
| `$getapi->sbi()` | `index()`, `search()`, `get()` (CBS SBI-codes) |

Elke succesvolle aanroep die gegevens teruggeeft kost credits. Lege resultaten en fouten zijn gratis. De actuele prijs per endpoint staat in de [documentatie](https://getapi.nl/docs). Een WOZ-waarde kost bijvoorbeeld meer credits dan een KVK-zoekopdracht.

### Voorbeelden

```php
// Bedrijf zoeken op naam (resultaten staan in $response->data['results'])
$response = $getapi->kvk()->search('Voorbeeld', null, 1, 10);
// Vanaf PHP 8.0 kan het ook met benoemde argumenten:
// $response = $getapi->kvk()->search(naam: 'Voorbeeld', size: 10);

// Adres opzoeken. Splits het adres in losse delen:
// "Hooidrift 161C-03" wordt huisnummer 161, letter C en toevoeging 03.
$adres = $getapi->address()->lookup('3023KN', 161, 'C', '03');

// BTW-nummer controleren: een ongeldig nummer geeft een antwoord met valid: false, geen fout.
$btw = $getapi->validation()->vat('NL820646660B01');
if ($btw->data['valid']) {
    // ...
}

// WOZ-waarde en energielabel
$woz = $getapi->property()->wozValue('3023KN', 161);
$label = $getapi->property()->energyLabel('3023KN', 161);
```

## Het antwoord

Elke methode geeft een `GetApi\Response` terug:

```php
$response->data();             // de gegevens als array (ook als alleen-lezen `$response->data`)
$response->statusCode();       // HTTP-status
$response->creditsRemaining(); // resterende credits, of null
$response->header('Content-Type'); // een willekeurige header
$response->get('naam', 'onbekend'); // één waarde met standaardwaarde
```

## Fouten afhandelen

Alle fouten van de API en de verbinding erven van `GetApi\Exceptions\GetApiException`. Ongeldige invoer die de client zelf afkeurt (een lege postcode, een te lange zoektekst) geeft een standaard `InvalidArgumentException`, nog voordat er een verzoek wordt verstuurd.

Bij een controle (BTW, IBAN, e-mail, KVK-nummer) is een ongeldige waarde geen fout maar een antwoord met `valid: false`. Een echte invoerfout, zoals een ontbrekend veld, geeft wel een `ValidationException`. Foutantwoorden zonder JSON (bijvoorbeeld een HTML-pagina van een proxy) worden op statuscode vertaald, dus ook dan krijg je een `RateLimitException` of `UpstreamUnavailableException`.

| Exception | Wanneer |
| --- | --- |
| `AuthException` | Key ontbreekt of is ongeldig (401), of geen toegang (403) |
| `CreditsExhaustedException` | Credits op (402). `getTopupUrl()` geeft de opwaardeerlink |
| `NotFoundException` | Niets gevonden (404). Kost geen credits |
| `ValidationException` | Ongeldige invoer (422). `getErrors()` geeft de meldingen per veld |
| `RateLimitException` | Limiet per minuut bereikt (429). `getRetryAfter()` geeft de wachttijd in seconden |
| `UpstreamUnavailableException` | De bron (KVK, VIES, enzovoort) is tijdelijk niet beschikbaar (503) |
| `ConnectionException` | De API is niet bereikt (netwerkfout of time-out) |
| `ApiException` | Alle andere foutstatussen |

```php
use GetApi\Exceptions\CreditsExhaustedException;
use GetApi\Exceptions\NotFoundException;

try {
    $bedrijf = $getapi->kvk()->get('00000000');
} catch (NotFoundException $e) {
    // Onbekend KVK-nummer
} catch (CreditsExhaustedException $e) {
    header('Location: ' . $e->getTopupUrl());
}
```

## Eigen HTTP-client

De client gebruikt PSR-18, dus elke HTTP-client werkt. Zonder instelling zoekt `php-http/discovery` zelf een geïnstalleerde client. Je kunt ook je eigen client meegeven, bijvoorbeeld om een time-out in te stellen of in tests een nep-client te gebruiken:

```php
$getapi = new GetApi\GetApi('ga_live_...', new \GuzzleHttp\Client(['timeout' => 10]));
```

De client stelt zelf geen time-out in en probeert een verzoek na een 429 niet opnieuw. Beide hangen af van de HTTP-client die je meegeeft. Stel dus een time-out in en gebruik `RateLimitException::getRetryAfter()` als je zelf wilt herhalen. De basis-URL moet `https://` zijn, zodat je API-key niet onversleuteld over de lijn gaat (alleen `localhost` mag `http` gebruiken).

In je eigen tests kun je `GetApi` en de resources (`$getapi->kvk()` enzovoort) mocken, want de klassen zijn niet `final`.

Installeer zelf een PSR-18-client als je er nog geen hebt, bijvoorbeeld `composer require guzzlehttp/guzzle`.

## Eisen

PHP 7.2 of hoger, met de extensies `json` en `mbstring`. De voorbeelden met benoemde argumenten werken pas vanaf PHP 8.0. De tests draaien in de CI op PHP 7.2 tot en met 8.5.

## Tests draaien

```bash
composer install
composer test
```

De tests gebruiken een nep-HTTP-client en doen geen echte aanroepen en kosten dus geen credits.

## Hulp en documentatie

- [Documentatie](https://getapi.nl/docs)
- [Problemen melden](https://github.com/GetApiNL/getapi-php/issues)

## Over GetAPI

GetAPI is een label van [WD Noord](https://wdnoord.nl/?utm_source=packagist&utm_medium=sdk&utm_campaign=getapi-php-readme&utm_content=footer).

## Licentie

[MIT](LICENSE)

---

*English: official PHP client for GetAPI.nl, a single-key API for Dutch company (KVK), VAT (VIES), BAG address, WOZ and energy label data. Requires PHP 7.2+, uses PSR-18. See the examples above.*
