<?php

declare(strict_types=1);

namespace GetApi\Tests;

use GetApi\GetApi;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Gedeelde hulpmiddelen voor de tests: een nep-HTTP-client en een client die daarop draait.
 */
abstract class TestCase extends BaseTestCase
{
    /** @var MockClient */
    protected $http;

    protected function setUp(): void
    {
        $this->http = new MockClient(new Psr17Factory());
    }

    /**
     * Maak een GetApi-client die het opgegeven antwoord teruggeeft.
     *
     * @param array<string, mixed>|null $body    JSON-inhoud van het antwoord.
     * @param array<string, string>     $headers Extra headers in het antwoord.
     */
    protected function clientReturning(int $status, ?array $body = [], array $headers = []): GetApi
    {
        $this->http->addResponse(new Response(
            $status,
            ['Content-Type' => 'application/json'] + $headers,
            $body === null ? '' : (string) json_encode($body)
        ));

        $factory = new Psr17Factory();

        return new GetApi('ga_live_test', $this->http, $factory, $factory);
    }

    protected function lastRequest(): RequestInterface
    {
        $request = $this->http->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $request, 'Er is geen verzoek verstuurd.');

        return $request;
    }

    /**
     * @return array<string, string>
     */
    protected function lastQuery(): array
    {
        parse_str($this->lastRequest()->getUri()->getQuery(), $query);

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    protected function lastJsonBody(): array
    {
        $decoded = json_decode((string) $this->lastRequest()->getBody(), true);
        self::assertIsArray($decoded, 'De body van het verzoek is geen geldige JSON.');

        return $decoded;
    }
}
