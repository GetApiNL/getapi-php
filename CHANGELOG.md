# Wijzigingen

Dit project volgt [Semantic Versioning](https://semver.org/lang/nl/).

## 1.1.0

- Foutantwoorden zonder JSON worden nu op statuscode vertaald, dus een 429 of 503 geeft weer `RateLimitException` of `UpstreamUnavailableException`.
- Bij controles (BTW, IBAN, e-mail, KVK) is alleen een 422 met een `valid`-sleutel een antwoord. Andere 422-antwoorden geven een `ValidationException`.
- `ext-json` en `ext-mbstring` toegevoegd aan `composer.json`.
- `GetApi` en de resources zijn niet meer `final`, zodat je ze kunt mocken. `Response` heeft getters (`data()`, `statusCode()`, `creditsRemaining()`) en is echt alleen-lezen.
- Een `baseUrl` met `http://` wordt geweigerd, behalve voor localhost.
- De README gebruikt de juiste pakketnaam `getapinl/getapi-php`.
- De minimale PHP-versie is verlaagd van 8.1 naar 7.2. De publieke API blijft gelijk. `Response::$data`, `$statusCode` en `$creditsRemaining` zijn niet meer `readonly` en moeten als alleen-lezen worden behandeld.

## 1.0.0

- Eerste versie: KVK, adres (BAG), pand (WOZ, energielabel, pandgegevens), controles (BTW, IBAN, e-mail, DNSBL), opzoeken (IP, DNS), RDW-voertuig en CBS SBI-codes.
- PSR-18 voor HTTP, met automatische client-detectie.
- Duidelijke exceptions per foutsoort, met Nederlandse meldingen.
