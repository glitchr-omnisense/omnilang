<?php

namespace Omnilang\Tests;

use Omnilang\Exception\ProviderException;
use Omnilang\Exception\UnreachableException;
use Omnilang\Exception\UsageLimitReachedException;
use Omnilang\Http\JsonClient;
use Omnisense\Contracts\Policy\Backoff;
use Omnisense\Contracts\Policy\InMemoryRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class JsonClientTest extends TestCase
{
    public function testAFileAnswerWhateverTheMethod(): void
    {
        $http = new MockHttpClient(new MockResponse("PK\x03\x04docx", ['http_code' => 200, 'response_headers' => ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document; charset=binary']]));
        $file = (new JsonClient($http, 'Example'))->binary('POST', 'https://api.example.org/v2/document/1/result', ['json' => ['document_key' => 'k-123456']]);

        self::assertSame(["PK\x03\x04docx", 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], [$file['body'] ?? null, $file['type'] ?? null]);
        self::assertNull((new JsonClient(new MockHttpClient(new MockResponse('', ['http_code' => 404])), 'Example'))->binary('POST', 'https://api.example.org/x'));
    }

    public function testASecretTheRequestDoesNotShowIsMaskedToo(): void
    {
        $http = new MockHttpClient(new MockResponse('{"message": "signature of AKIDSECRETSIGNINGKEY does not match"}', ['http_code' => 403]));
        try {
            (new JsonClient($http, 'Example', [], null, ['AKIDSECRETSIGNINGKEY']))->request('POST', 'https://api.example.org/', ['body' => '{}']);
            self::fail('A 403 is an error.');
        } catch (ProviderException $e) {
            self::assertSame([403, 'Example answered 403 for POST /. {"message": "signature of *** does not match"}'], [$e->status, $e->getMessage()]);
        }

        $down = new MockHttpClient(static fn () => throw new TransportException('Proxy refused AKIDSECRETSIGNINGKEY'));
        try {
            (new JsonClient($down, 'Example', [], null, ['AKIDSECRETSIGNINGKEY']))->binary('GET', 'https://api.example.org/file');
            self::fail('A transport failure is an error.');
        } catch (UnreachableException $e) {
            self::assertStringNotContainsString('AKIDSECRETSIGNINGKEY', $e->getMessage().($e->getPrevious()?->getMessage() ?? ''));
        }
    }

    public function testA429HoldsTheNextCallsForTheWaitAsked(): void
    {
        $limiter = new InMemoryRateLimiter();
        $http = new MockHttpClient([new MockResponse('{"message": "Too many requests"}', ['http_code' => 429, 'response_headers' => ['Retry-After' => '30']])]);
        $json = new JsonClient($http, 'Example', [], new Backoff($limiter, 'example', UsageLimitReachedException::class));
        try {
            $json->request('POST', 'https://api.example.org/v2/translate', ['json' => []]);
            self::fail('A 429 is a limit reached.');
        } catch (UsageLimitReachedException $e) {
            self::assertSame(30, $e->retryAfter);
        }

        $this->expectException(UsageLimitReachedException::class);
        $json->request('POST', 'https://api.example.org/v2/translate', ['json' => []]);
    }
}
