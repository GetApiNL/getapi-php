<?php

declare(strict_types=1);

namespace GetApi\Tests;

use GetApi\Exceptions\ValidationException;
use InvalidArgumentException;

/**
 * Controleert per resource dat het juiste pad, de juiste methode en de juiste parameters worden verstuurd.
 */
final class ResourcesTest extends TestCase
{
    // ----- KVK -----

    public function testKvkZoekenOpNaam(): void
    {
        $this->clientReturning(200, ['results' => []])->kvk()->search('Voorbeeld', null, 1, 10);

        self::assertSame('GET', $this->lastRequest()->getMethod());
        self::assertSame('/v1/kvk/search', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['naam' => 'Voorbeeld', 'page' => '1', 'size' => '10'], $this->lastQuery());
    }

    public function testKvkZoekenLaatLegeParametersWeg(): void
    {
        $this->clientReturning(200)->kvk()->search(null, '53743792');

        self::assertArrayNotHasKey('naam', $this->lastQuery());
        self::assertSame('53743792', $this->lastQuery()['kvkNummer']);
    }

    public function testKvkZoekenZonderNaamEnNummerGeeftFout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Geef een naam, een KVK-nummer of allebei op');

        $this->clientReturning(200)->kvk()->search();
    }

    public function testKvkZoekenMetOnbekendePaginagrootteGeeftFout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->clientReturning(200)->kvk()->search('Voorbeeld', null, 1, 30);
    }

    public function testKvkValidateIsEenAntwoordBijOngeldig(): void
    {
        $response = $this->clientReturning(422, ['found' => false, 'valid' => false, 'kvk_number' => '123'])
            ->kvk()->validate('123');

        self::assertSame('POST', $this->lastRequest()->getMethod());
        self::assertSame('/v1/kvk/validate', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['kvkNummer' => '123'], $this->lastJsonBody());
        self::assertSame('application/json', $this->lastRequest()->getHeaderLine('Content-Type'));
        self::assertSame(422, $response->statusCode);
        self::assertFalse($response->data['valid']);
    }

    // ----- Adres -----

    public function testAdresOpzoekenGebruiktLosseDelen(): void
    {
        $this->clientReturning(200, ['found' => true])->address()->lookup('3023KN', 161, 'C', '03');

        self::assertSame('/v1/kadaster/bag/address', $this->lastRequest()->getUri()->getPath());
        self::assertSame([
            'postcode' => '3023KN',
            'house_number' => '161',
            'house_letter' => 'C',
            'house_number_addition' => '03',
        ], $this->lastQuery());
    }

    public function testAdresOpzoekenLaatLetterEnToevoegingWegAlsDieLeegZijn(): void
    {
        $this->clientReturning(200)->address()->lookup('3023KN', 161);

        self::assertSame(['postcode' => '3023KN', 'house_number' => '161'], $this->lastQuery());
    }

    public function testAdresSuggesties(): void
    {
        $this->clientReturning(200, ['suggestions' => []])->address()->suggest('Hooidrift 161', 5);

        self::assertSame('/v1/kadaster/bag/address-suggest', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['q' => 'Hooidrift 161', 'limit' => '5'], $this->lastQuery());
    }

    public function testAdresSuggestiesMetTeKorteTekstGeeftFout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->clientReturning(200)->address()->suggest('H');
    }

    public function testAdresOpBagId(): void
    {
        $this->clientReturning(200)->address()->byId('0599200000200000');

        self::assertSame('/v1/kadaster/bag/id', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['id' => '0599200000200000'], $this->lastQuery());
    }

    public function testAdresOpBagIdMetVerkeerdeLengteGeeftFout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('16 cijfers');

        $this->clientReturning(200)->address()->byId('123');
    }

    public function testPostcodeOpzoeken(): void
    {
        $this->clientReturning(200)->address()->postcode('3023KN', 161);

        self::assertSame('/v1/lookup/postcode', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['postcode' => '3023KN', 'number' => '161'], $this->lastQuery());
    }

    // ----- Pand -----

    public function testWozWaarde(): void
    {
        $this->clientReturning(200)->property()->wozValue('3023KN', 161, 'C');

        self::assertSame('/v1/property/woz-waarde', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['postcode' => '3023KN', 'house_number' => '161', 'house_letter' => 'C'], $this->lastQuery());
    }

    public function testEnergielabelGebruiktZipcode(): void
    {
        $this->clientReturning(200)->property()->energyLabel('3023KN', 161);

        self::assertSame('/v1/property/energy-label', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['zipcode' => '3023KN', 'house_number' => '161'], $this->lastQuery());
    }

    public function testPandgegevens(): void
    {
        $this->clientReturning(200)->property()->details('3023KN', 161);

        self::assertSame('/v1/property/pandgegevens', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['postcode' => '3023KN', 'house_number' => '161'], $this->lastQuery());
    }

    // ----- Controles -----

    public function testBtwControleVerwijdertSpaties(): void
    {
        $this->clientReturning(200, ['valid' => true])->validation()->vat('NL 8206 46660 B01');

        self::assertSame('POST', $this->lastRequest()->getMethod());
        self::assertSame('/v1/validate/vat', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['vat_number' => 'NL820646660B01'], $this->lastJsonBody());
    }

    public function testOngeldigBtwNummerIsEenAntwoordGeenFout(): void
    {
        $response = $this->clientReturning(422, ['valid' => false, 'message' => 'BTW-nummer is ongeldig.'])
            ->validation()->vat('NL000000000B00');

        self::assertFalse($response->data['valid']);
        self::assertSame(422, $response->statusCode);
    }

    public function testIbanMetBic(): void
    {
        $this->clientReturning(200, ['valid' => true])->validation()->iban('NL91ABNA0417164300', 'ABNANL2A');

        self::assertSame('/v1/validate/iban', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['iban' => 'NL91ABNA0417164300', 'bic' => 'ABNANL2A'], $this->lastJsonBody());
    }

    public function testIbanZonderBicStuurtGeenBicMee(): void
    {
        $this->clientReturning(200)->validation()->iban('NL91ABNA0417164300');

        self::assertSame(['iban' => 'NL91ABNA0417164300'], $this->lastJsonBody());
    }

    public function testEmailControle(): void
    {
        $this->clientReturning(200)->validation()->email('naam@voorbeeld.nl');

        self::assertSame('/v1/validate/email', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['email' => 'naam@voorbeeld.nl'], $this->lastJsonBody());
    }

    public function testEmailBatchBegrensdOp25(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->clientReturning(200)->validation()->emailBatch(array_fill(0, 26, 'naam@voorbeeld.nl'));
    }

    public function testEmailBatchVerstuurtDeLijst(): void
    {
        $this->clientReturning(200)->validation()->emailBatch(['a@voorbeeld.nl', 'b@voorbeeld.nl']);

        self::assertSame('/v1/validate/email/batch', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['emails' => ['a@voorbeeld.nl', 'b@voorbeeld.nl']], $this->lastJsonBody());
    }

    public function testDnsblGeeftOngeldigeInvoerAlsFout(): void
    {
        $this->expectException(ValidationException::class);

        $this->clientReturning(422, ['message' => 'Ongeldig IP-adres.', 'errors' => ['ip' => ['Ongeldig.']]])
            ->validation()->dnsbl('geen-ip');
    }

    // ----- Opzoeken, voertuig en SBI -----

    public function testIpOpzoeken(): void
    {
        $this->clientReturning(200)->lookup()->ip('8.8.8.8');

        self::assertSame('/v1/lookup/ip', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['ip' => '8.8.8.8'], $this->lastQuery());
    }

    public function testDnsRecordsZetTypeInHoofdletters(): void
    {
        $this->clientReturning(200)->lookup()->dnsRecords('getapi.nl', 'mx');

        self::assertSame('/v1/lookup/dns/records', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['domain' => 'getapi.nl', 'type' => 'MX'], $this->lastQuery());
    }

    public function testDnsRecordsMetOnbekendTypeGeeftFout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->clientReturning(200)->lookup()->dnsRecords('getapi.nl', 'XYZ');
    }

    public function testKentekenWordtGeschoond(): void
    {
        $this->clientReturning(200)->vehicle()->get('ab-123-c');

        self::assertSame('/v1/rdw/vehicle', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['license_plate' => 'AB123C'], $this->lastQuery());
    }

    public function testSbiZoeken(): void
    {
        $this->clientReturning(200)->sbi()->search('software', 10);

        self::assertSame('/v1/cbs/sbi/search', $this->lastRequest()->getUri()->getPath());
        self::assertSame(['q' => 'software', 'limit' => '10'], $this->lastQuery());
    }

    public function testSbiCodeOpzoeken(): void
    {
        $this->clientReturning(200)->sbi()->get('62');

        self::assertSame('/v1/cbs/sbi/62', $this->lastRequest()->getUri()->getPath());
    }
}
