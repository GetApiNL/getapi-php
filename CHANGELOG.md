# Wijzigingen

Dit project volgt [Semantic Versioning](https://semver.org/lang/nl/).

## Niet uitgebracht

- De minimale PHP-versie is verlaagd van 8.1 naar 7.2. De publieke API blijft gelijk. `Response::$data`, `$statusCode` en `$creditsRemaining` zijn niet meer `readonly` en moeten als alleen-lezen worden behandeld.

## 1.0.0

- Eerste versie: KVK, adres (BAG), pand (WOZ, energielabel, pandgegevens), controles (BTW, IBAN, e-mail, DNSBL), opzoeken (IP, DNS), RDW-voertuig en CBS SBI-codes.
- PSR-18 voor HTTP, met automatische client-detectie.
- Duidelijke exceptions per foutsoort, met Nederlandse meldingen.
