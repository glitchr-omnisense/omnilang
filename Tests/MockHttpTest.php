<?php

namespace Omnilang\Tests;

use Omnilang\Http\Multipart;
use Omnilang\Model\Document;
use Omnilang\Testing\MockHttp;
use PHPUnit\Framework\TestCase;

/** Testing\MockHttp over omnisense/contracts' one: the requests kept as they went, form() for a multipart body. */
final class MockHttpTest extends TestCase
{
    public function testTheRequestsAreKeptAsSent(): void
    {
        $http = MockHttp::routes(['~^POST https://api\.example\.org/v2/translate$~' => [200, '{"translations": []}', ['Retry-After' => '3']]]);

        $response = $http->client->request('POST', 'https://api.example.org/v2/translate', ['json' => ['text' => ['Bonjour']], 'headers' => ['Authorization' => 'Key not-a-real-key']]);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['3'], $response->getHeaders()['retry-after']);
        self::assertCount(1, $http->requests, 'the kernel\'s requests, seen through this one');
        self::assertSame(['POST https://api.example.org/v2/translate'], $http->lines());
        self::assertSame('Key not-a-real-key', $http->header('authorization'));
        self::assertSame(['text' => ['Bonjour']], $http->json());
        self::assertStringContainsString('not-a-real-key', $http->dump());

        self::assertSame(404, $http->client->request('GET', 'https://api.example.org/other')->getStatusCode());
        self::assertCount(2, $http->requests);
        self::assertSame('GET', $http->request()['method']);
    }

    public function testFormAndFieldsReadTheBody(): void
    {
        $http = MockHttp::routes(['~^POST ~' => [200, '{}']]);

        $multipart = Multipart::encode(['target_lang' => 'EN'], ['file' => new Document('Bonjour', 'a.txt', 'text/plain')]);
        $http->client->request('POST', 'https://api.example.org/document', ['headers' => $multipart['headers'], 'body' => $multipart['body']]);
        self::assertSame('EN', $http->form()['target_lang']['value']);
        self::assertSame('a.txt', $http->form()['file']['filename']);

        $http->client->request('POST', 'https://api.example.org/glossary', ['body' => ['name' => 'Menu', 'entries' => 'a']]);
        self::assertSame(['name' => 'Menu', 'entries' => 'a'], $http->fields());
    }
}
