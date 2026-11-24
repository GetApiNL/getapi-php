<?php

declare(strict_types=1);

namespace GetApi\Tests;

use GetApi\Exceptions\ApiException;
use GetApi\Exceptions\AuthException;
use GetApi\Exceptions\CreditsExhaustedException;
use GetApi\Exceptions\GetApiException;
use GetApi\Exceptions\NotFoundException;
use GetApi\Exceptions\RateLimitException;
use GetApi\Exceptions\UpstreamUnavailableException;
use GetApi\Exceptions\ValidationException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;

final class ErrorMappingTest extends TestCase
{
    /**
     * @return array<string, array{int, class-string<ApiException>}>
     */
    public static function statusProvider(): array
    {
        return [
            '401 ongeldige key' => [401, AuthException::class],
            '403 geen toegang' => [403, AuthException::class],
            '402 credits op' => [402, CreditsExhaustedException::class],
            '404 niet gevonden' => [404, NotFoundException::class],
            '422 ongeldige invoer' => [422, ValidationException::class],
            '429 te veel verzoeken' => [429, RateLimitException::class],
            '503 bron niet beschikbaar' => [503, UpstreamUnavailableException::class],
            '500 onverwacht' => [500, ApiException::class],
            '400 slecht verzoek' => [400, ApiException::class],
        ];
    }

    /**
     * @dataProvider statusProvider
     *
     * @param class-string<ApiException> $expected
     */
    public function testStatusWordtDeJuisteException(int $status, string $expected): void
    {
        $getapi = $this->clientReturning($status, ['message' => 'iets']);

        try {
            $getapi->kvk()->get('53743792');
            self::fail('Er had een exception moeten komen.');
        } catch (ApiException $e) {
            self::assertInstanceOf($expected, $e);
            self::assertInstanceOf(GetApiException::class, $e);
            self::assertSame($status, $e->getStatusCode());
            self::assertSame($status, $e->getCode());
        }
    }

    public function testCreditsOpGeeftTopupLink(): void
    {
        $getapi = $this->clientReturning(402, ['error' => 'insufficient_credits', 'topup_url' => 'https://getapi.nl/opwaarderen']);

        try {
            $getapi->kvk()->get('53743792');
            self::fail('Er had een exception moeten komen.');
        } catch (CreditsExhaustedException $e) {
            self::assertSame('https://getapi.nl/opwaarderen', $e->getTopupUrl());
            self::assertSame('insufficient_credits', $e->getErrorCode());
            self::assertStringContainsString('credits zijn op', $e->getMessage());
        }
    }

    public function testRateLimitGeeftWachttijdUitHeader(): void
    {
        $getapi = $this->clientReturning(429, ['error' => 'rate_limited', 'retry_after' => 30], ['Retry-After' => '12']);

        try {
            $getapi->kvk()->get('53743792');
            self::fail('Er had een exception moeten komen.');
        } catch (RateLimitException $e) {
            self::assertSame(12, $e->getRetryAfter());
            self::assertSame('rate_limited', $e->getErrorCode());
        }
    }

    public function testRateLimitValtTerugOpWachttijdUitBody(): void
    {
        $getapi = $this->clientReturning(429, ['error' => 'rate_limited', 'retry_after' => 30]);

        try {
            $getapi->kvk()->get('53743792');
            self::fail('Er had een exception moeten komen.');
        } catch (RateLimitException $e) {
            self::assertSame(30, $e->getRetryAfter());
        }
    }

    public function testValidatieFoutGeeftMeldingenPerVeld(): void
    {
        $getapi = $this->clientReturning(422, ['message' => 'De postcode is ongeldig.', 'errors' => ['postcode' => ['Ongeldig formaat.']]]);

        try {
            $getapi->address()->lookup('abc', 1);
            self::fail('Er had een exception moeten komen.');
        } catch (ValidationException $e) {
            self::assertSame(['postcode' => ['Ongeldig formaat.']], $e->getErrors());
            self::assertSame('De postcode is ongeldig.', $e->getMessage());
        }
    }

    public function testNietGevondenGeeftDeInhoudVanHetAntwoord(): void
    {
        $getapi = $this->clientReturning(404, ['found' => false, 'error' => 'not_found']);

        try {
            $getapi->address()->lookup('1234AB', 1);
            self::fail('Er had een exception moeten komen.');
        } catch (NotFoundException $e) {
            self::assertFalse($e->getData()['found']);
            self::assertSame('not_found', $e->getErrorCode());
        }
    }

    public function testAntwoordDatGeenJsonIsGeeftApiException(): void
    {
        $this->http->addResponse(new Response(200, [], '<html>storing</html>'));
        $factory = new Psr17Factory();
        $getapi = new \GetApi\GetApi('ga_live_test', $this->http, $factory, $factory);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('geen geldige JSON');

        $getapi->kvk()->get('53743792');
    }
}
