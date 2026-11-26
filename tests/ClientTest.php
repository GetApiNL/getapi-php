<?php

declare(strict_types=1);

namespace GetApi\Tests;

use GetApi\Exceptions\ConnectionException;
use GetApi\GetApi;
use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;

final class ClientTest extends TestCase
{
    public function testStuurtApiKeyEnClientHeadersMee(): void
    {
        $getapi = $this->clientReturning(200, ['naam' => 'Voorbeeld B.V.']);

        $getapi->kvk()->get('53743792');

        $request = $this->lastRequest();
        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://api.getapi.nl/v1/kvk/53743792', (string) $request->getUri());
        self::assertSame('ga_live_test', $request->getHeaderLine('X-Api-Key'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertSame('getapi-php/' . GetApi::VERSION, $request->getHeaderLine('X-GetAPI-Client'));
        self::assertStringStartsWith('getapi-php/' . GetApi::VERSION . ' (php/', $request->getHeaderLine('User-Agent'));
    }

    public function testWrapperKanEigenNaamEnPlatformMeegeven(): void
    {
        $this->http->addResponse(new \Nyholm\Psr7\Response(200, [], '{}'));
        $factory = new Psr17Factory();
        $getapi = new GetApi('ga_live_test', $this->http, $factory, $factory, GetApi::DEFAULT_BASE_URL, 'mijn-wrapper/1.0.0', 'mijn-app/2.0');

        $getapi->kvk()->get('53743792');

        self::assertSame('mijn-wrapper/1.0.0', $this->lastRequest()->getHeaderLine('X-GetAPI-Client'));
        self::assertStringContainsString('(php/', $this->lastRequest()->getHeaderLine('User-Agent'));
        self::assertStringContainsString('; mijn-app/2.0)', $this->lastRequest()->getHeaderLine('User-Agent'));
    }

    public function testLeegeApiKeyWordtGeweigerd(): void
    {
        $factory = new Psr17Factory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('De API-key mag niet leeg zijn');

        new GetApi('   ', $this->http, $factory, $factory);
    }

    public function testResponseBevatDataEnCredits(): void
    {
        $getapi = $this->clientReturning(200, ['naam' => 'Voorbeeld B.V.', 'actief' => true], ['X-Credits-Remaining' => '248']);

        $response = $getapi->kvk()->get('53743792');

        self::assertSame(200, $response->statusCode);
        self::assertSame('Voorbeeld B.V.', $response->data['naam']);
        self::assertSame('Voorbeeld B.V.', $response->get('naam'));
        self::assertSame('onbekend', $response->get('bestaatNiet', 'onbekend'));
        self::assertSame(248, $response->creditsRemaining);
        self::assertSame('248', $response->header('x-credits-remaining'));
        self::assertSame('248', $response->header('X-Credits-Remaining'));
    }

    public function testCreditsOntbrekenAlsDeHeaderOntbreekt(): void
    {
        $getapi = $this->clientReturning(200, ['naam' => 'Voorbeeld B.V.']);

        self::assertNull($getapi->kvk()->get('53743792')->creditsRemaining);
    }

    public function testNetwerkfoutWordtConnectionException(): void
    {
        $this->http->addException(new \Http\Client\Exception\NetworkException('time-out', new \Nyholm\Psr7\Request('GET', 'https://api.getapi.nl')));
        $factory = new Psr17Factory();
        $getapi = new GetApi('ga_live_test', $this->http, $factory, $factory);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Kon GetAPI niet bereiken');

        $getapi->kvk()->get('53743792');
    }

    public function testResourcesWordenHergebruikt(): void
    {
        $getapi = $this->clientReturning(200);

        self::assertSame($getapi->kvk(), $getapi->kvk());
        self::assertSame($getapi->address(), $getapi->address());
    }
}
