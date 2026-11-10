<?php

namespace Omnilang\Testing;

use Omnisense\Contracts\Testing\MockHttp as KernelMockHttp;
use Symfony\Component\HttpClient\MockHttpClient;

/**
 * Symfony's MockHttpClient answering by route, and every request it got
 * kept as it went on the wire: the method, the full URL (query included),
 * the headers by lower-case name, the body as bytes. A provider's tests
 * assert the exact request - path, query, headers, JSON or multipart body
 * - and that no key leaks, with no network.
 *
 *   $http = MockHttp::routes(['~^POST https://api-free\.deepl\.com/v2/translate$~' => [200, '@'.__DIR__.'/Fixtures/translate.json']]);
 *   $provider = (new DeeplFactory($http->client))->create(['key' => 'not-a-real-key:fx']);
 *   $http->json();                 // the last request's JSON body, decoded
 *   $http->header('authorization'); // "DeepL-Auth-Key not-a-real-key:fx"
 *
 * A route's body naming a file ("@/path/answer.json") is read from it; its
 * headers are sent with it (a 429's Retry-After). Nothing matched: 404.
 *
 * omnisense/contracts' Testing\MockHttp does the work (it is final: this one
 * holds it); form() reads a multipart body, which only omnilang has
 * (Http\Multipart).
 *
 * @suggested symfony/http-client (test support: MockHttpClient)
 */
final class MockHttp
{
    /** @var list<array{method: string, url: string, headers: array<string, string>, body: string}> the kernel's, as it fills */
    public array $requests = [];

    public readonly MockHttpClient $client;

    private function __construct(private readonly KernelMockHttp $http)
    {
        $this->client = $http->client;
        $this->requests = &$http->requests;
    }

    /** @param array<string, array{0: int, 1: string, 2?: array<string, string|list<string>>}|\Closure(string, string, string): array{0: int, 1: string, 2?: array<string, string|list<string>>}> $routes "METHOD url" patterns */
    public static function routes(array $routes): self
    {
        return new self(KernelMockHttp::routes($routes));
    }

    /** @return array{method: string, url: string, headers: array<string, string>, body: string} */
    public function request(int $index = -1): array
    {
        return $this->http->request($index);
    }

    /**
     * "METHOD url" of each request, in order.
     *
     * @return list<string>
     */
    public function lines(): array
    {
        return $this->http->lines();
    }

    /** A request's header (its values joined by ", " when it was sent several). */
    public function header(string $name, int $index = -1): ?string
    {
        return $this->http->header($name, $index);
    }

    /** A request's JSON body, decoded. */
    public function json(int $index = -1): mixed
    {
        return $this->http->json($index);
    }

    /**
     * A request's form fields (application/x-www-form-urlencoded).
     *
     * @return array<array-key, mixed>
     */
    public function fields(int $index = -1): array
    {
        return $this->http->fields($index);
    }

    /**
     * A request's multipart fields (Multipart::decode()).
     *
     * @return array<string, array{value: string, filename: ?string, type: ?string}>
     */
    public function form(int $index = -1): array
    {
        $request = $this->request($index);

        return \Omnilang\Http\Multipart::decode($request['headers']['content-type'] ?? '', $request['body']);
    }

    /** Every request, as one string: what a test greps for a key that must not be there. */
    public function dump(): string
    {
        return $this->http->dump();
    }
}
