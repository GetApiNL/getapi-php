<?php

declare(strict_types=1);

namespace GetApi\Http;

use GetApi\Exceptions\ApiException;
use GetApi\Exceptions\AuthException;
use GetApi\Exceptions\ConnectionException;
use GetApi\Exceptions\CreditsExhaustedException;
use GetApi\Exceptions\NotFoundException;
use GetApi\Exceptions\RateLimitException;
use GetApi\Exceptions\UpstreamUnavailableException;
use GetApi\Exceptions\ValidationException;
use GetApi\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Verstuurt de verzoeken naar de API en vertaalt de antwoorden naar een Response of een exception.
 *
 * Deze klasse is intern. Gebruik de resources op GetApi (kvk(), address(), enzovoort).
 *
 * @internal
 */
final class Transport
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $userAgent,
        private readonly string $clientName,
    ) {
    }

    /**
     * Stuurt een GET-verzoek.
     *
     * @param array<string, scalar|null> $query Queryparameters. Lege waarden (null en lege tekst) worden weggelaten.
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->send('GET', $path, $query, null, false);
    }

    /**
     * Stuurt een POST-verzoek met een JSON-body.
     *
     * @param array<string, mixed> $body
     * @param bool                 $invalidIsAnswer Zet op true bij controles (BTW, IBAN, e-mail). Een ongeldige invoer
     *                                              (HTTP 422) is dan een antwoord (`valid: false`) en geen fout.
     */
    public function post(string $path, array $body, bool $invalidIsAnswer = false): Response
    {
        return $this->send('POST', $path, [], $body, $invalidIsAnswer);
    }

    /**
     * @param array<string, scalar|null> $query
     * @param array<string, mixed>|null  $body
     */
    private function send(string $method, string $path, array $query, ?array $body, bool $invalidIsAnswer): Response
    {
        $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '');

        $url = rtrim($this->baseUrl, '/') . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $request = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-Api-Key', $this->apiKey)
            ->withHeader('User-Agent', $this->userAgent)
            ->withHeader('X-GetAPI-Client', $this->clientName);

        if ($body !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ConnectionException(
                'Kon GetAPI niet bereiken: ' . $e->getMessage() . '. Controleer je internetverbinding en probeer het opnieuw.',
                0,
                $e,
            );
        }

        return $this->handle($response, $invalidIsAnswer);
    }

    private function handle(ResponseInterface $response, bool $invalidIsAnswer): Response
    {
        $status = $response->getStatusCode();
        $headers = $this->normalizeHeaders($response);
        $raw = (string) $response->getBody();

        $data = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw new ApiException(
                    'GetAPI gaf een antwoord dat geen geldige JSON is (HTTP ' . $status . ').',
                    $status,
                );
            }
            $data = $decoded;
        }

        $credits = isset($headers['x-credits-remaining']) && is_numeric($headers['x-credits-remaining'])
            ? (int) $headers['x-credits-remaining']
            : null;

        if ($status >= 200 && $status < 300) {
            return new Response($data, $status, $credits, $headers);
        }

        if ($status === 422 && $invalidIsAnswer) {
            return new Response($data, $status, $credits, $headers);
        }

        throw $this->exceptionFor($status, $data, $headers);
    }

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     */
    private function exceptionFor(int $status, array $data, array $headers): ApiException
    {
        $apiMessage = isset($data['message']) && is_string($data['message']) && $data['message'] !== ''
            ? $data['message']
            : null;

        switch ($status) {
            case 401:
                return new AuthException('Ongeldige of ontbrekende API-key. Controleer je key op https://getapi.nl.', $status, $data);
            case 403:
                return new AuthException('Deze API-key heeft geen toegang tot dit endpoint.', $status, $data);
            case 402:
                $topup = isset($data['topup_url']) && is_string($data['topup_url']) ? $data['topup_url'] : null;

                return new CreditsExhaustedException(
                    'Je credits zijn op. Waardeer je saldo op of wacht tot je maandelijkse credits worden aangevuld.',
                    $status,
                    $data,
                    $topup,
                );
            case 404:
                return new NotFoundException($apiMessage ?? 'Niets gevonden voor deze aanvraag.', $status, $data);
            case 422:
                $errors = isset($data['errors']) && is_array($data['errors']) ? $data['errors'] : [];

                return new ValidationException($apiMessage ?? 'De invoer is ongeldig.', $status, $data, $errors);
            case 429:
                $retryAfter = null;
                if (isset($headers['retry-after']) && is_numeric($headers['retry-after'])) {
                    $retryAfter = (int) $headers['retry-after'];
                } elseif (isset($data['retry_after']) && is_numeric($data['retry_after'])) {
                    $retryAfter = (int) $data['retry_after'];
                }

                return new RateLimitException(
                    'Te veel verzoeken per minuut voor deze API-key. Probeer het over ' . ($retryAfter ?? 'enkele') . ' seconden opnieuw.',
                    $status,
                    $data,
                    $retryAfter,
                );
            case 503:
                return new UpstreamUnavailableException(
                    $apiMessage ?? 'De onderliggende bron (bijvoorbeeld KVK of VIES) is tijdelijk niet beschikbaar. Probeer het later opnieuw.',
                    $status,
                    $data,
                );
            default:
                return new ApiException($apiMessage ?? 'GetAPI gaf een onverwachte fout (HTTP ' . $status . ').', $status, $data);
        }
    }

    /**
     * @return array<string, string>
     */
    private function normalizeHeaders(ResponseInterface $response): array
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[strtolower((string) $name)] = (string) ($values[0] ?? '');
        }

        return $headers;
    }
}
