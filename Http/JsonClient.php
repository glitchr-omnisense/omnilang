<?php

namespace Omnilang\Http;

use Omnilang\Exception\ProviderException;
use Omnilang\Exception\UnreachableException;
use Omnisense\Contracts\Policy\Backoff;
use Omnisense\Contracts\Policy\UsageLimitReached;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * A provider's JSON calls over the application's HTTP client: the
 * provider's headers on every request, a 404 read as "not known" (null),
 * any other error status as a ProviderException (its $status kept) naming
 * the path only, a network failure as an UnreachableException naming the
 * path only too; credentials kept out of every message, copy and trace
 * (omnisense/contracts' JsonClient underneath).
 *
 * Given the provider's Backoff (Config::backoff() in a factory's build()),
 * it waits while the provider asked to and reads every response's
 * Retry-After and RateLimit fields: a 429 is a UsageLimitReachedException
 * with the wait asked.
 *
 * Besides: binary() for an answer that is a file, whatever the method
 * (DeepL's translated document, Azure's); $secrets for credentials that
 * travel where the kernel does not look for them (a multipart field, a
 * signing key), masked in every error as well.
 */
final class JsonClient extends \Omnisense\Contracts\Http\JsonClient
{
    /**
     * @param array<string, string> $defaultHeaders sent with every request
     * @param list<string>          $secrets masked in every error, besides those the request shows
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        string $provider,
        private readonly array $defaultHeaders = [],
        private readonly ?Backoff $wait = null,
        #[\SensitiveParameter]
        private readonly array $secrets = [],
    ) {
        parent::__construct($client, $provider, $defaultHeaders, $wait);
    }

    /**
     * @param array<string, string|int|float|bool|null> $query
     * @param array<string, string>                     $headers
     *
     * @return ?Data null when the provider answers 404
     */
    public function get(string $url, array $query = [], array $headers = []): ?Data
    {
        return self::own(parent::get($url, $query, $headers));
    }

    /**
     * @param array<string, mixed>  $options json, body or query, as Symfony's HttpClient takes them
     * @param array<string, string> $headers
     *
     * @return ?Data null when the provider answers 404
     */
    public function request(string $method, string $url, array $options = [], array $headers = []): ?Data
    {
        return self::own(parent::request($method, $url, $options, $headers));
    }

    /**
     * An answer that is a file, with the same errors as request().
     *
     * @param array<string, mixed>  $options
     * @param array<string, string> $headers
     *
     * @return ?array{body: string, type: ?string} null when the provider answers 404; $type its Content-Type
     */
    public function binary(string $method, string $url, array $options = [], array $headers = []): ?array
    {
        $what = $method.' '.(parse_url($url, \PHP_URL_PATH) ?: '/');
        $options = ['headers' => $headers + $this->defaultHeaders] + $options;
        $secrets = [...Redaction::secrets($url, $options), ...$this->secrets];
        try {
            $this->wait?->clear();
            $response = $this->client->request($method, $url, $options);
            $status = $response->getStatusCode();
            $responseHeaders = $response->getHeaders(false);
            $this->wait?->observe($status, $responseHeaders);
            if (404 === $status) {
                return null;
            }
            $body = $response->getContent(false);
        } catch (UsageLimitReached $e) {
            throw Redaction::withoutArguments($e);
        } catch (ExceptionInterface $e) {
            throw Redaction::withoutArguments($this->unreachable(\sprintf('%s could not be reached for %s: %s', $this->provider, $what, Redaction::text(self::reason($e->getMessage()), $secrets)), Redaction::copy($e, $secrets)));
        }
        if ($status >= 400) {
            throw Redaction::withoutArguments($this->answered($what, $status, Redaction::text($body, $secrets)));
        }
        $type = $responseHeaders['content-type'][0] ?? null;

        return ['body' => $body, 'type' => \is_string($type) ? trim(explode(';', $type)[0]) : null];
    }

    /** @param array<mixed> $data */
    protected function data(array $data): Data
    {
        return new Data($data);
    }

    protected function answered(string $what, int $status, string $detail): \Throwable
    {
        return ProviderException::answered($this->provider, $what, $status, Redaction::text($detail, $this->secrets));
    }

    protected function unreachable(string $message, \Throwable $previous): \Throwable
    {
        return new UnreachableException(Redaction::text($message, $this->secrets), 0, [] === $this->secrets ? $previous : Redaction::copy($previous, $this->secrets));
    }

    private static function own(?\Omnisense\Contracts\Http\Data $data): ?Data
    {
        return null === $data || $data instanceof Data ? $data : new Data($data->toArray());
    }
}
