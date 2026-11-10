<?php

namespace Omnilang\Tests;

use Omnilang\Http\Data;
use Omnilang\Registry;
use Omnilang\Testing\BareTestCase;

/**
 * omnisense/omnilang with no framework: the harness's bare script
 * translates through a fallback, detects, makes and applies a glossary,
 * translates a document, and makes a provider's JSON call, in a PHP
 * process of its own - nothing may load from outside the package but
 * omnisense/contracts and Symfony's HTTP client contracts (interfaces only).
 */
final class BareTest extends BareTestCase
{
    protected static function entryClass(): string
    {
        return Registry::class;
    }

    protected static function requiredPackages(): array
    {
        return ['symfony/http-client-contracts'];
    }

    public function testTranslationReadByHandLoadsNothingButOmnilangAndWhatItRequires(): void
    {
        $report = Data::of(self::php([__DIR__.'/../docker/harness/bin/bare']));

        self::assertSame(['translate', 'detect', 'glossary', 'documents'], $report->get('capabilities.contracts'));
        self::assertSame(['Hello', 'plain'], $report->get('translate'), 'the first engine of the order');
        self::assertSame(['<p>Thank you</p>', 'rich'], $report->get('html'), 'the first one keeps no tags: passed over');
        self::assertSame([['de', 'rich']], $report->get('detect'), 'the first engine does not know the text');
        self::assertSame(['Twin room', 'rich', 'fixed-1'], $report->get('glossary'));
        self::assertSame(['done', 'plain', "Hello\nChambre double\n", 'menu.en.txt'], $report->get('document'));
        self::assertSame(['Hello', 'lang-bare/1.0'], $report->get('json'));
        self::assertNull($report->get('json-404'));
        self::assertSame(['Example answered 500 for POST /broken. Internal error', 500, false], $report->get('json-error'));

        $symbols = $report->strings('loaded.symbols');
        self::assertContains('Omnilang\Testing\FixedProvider', $symbols);
        self::assertSame([], self::foreign($symbols), 'no class of a framework, an HTTP client or an application');
        self::assertSame([], self::outside($report->strings('loaded.files'), $report->strings('loaded.eager')), 'no file but the package and what it requires');
    }
}
