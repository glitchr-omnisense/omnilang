<?php

namespace Omnilang\Tests;

use Omnilang\Config;
use Omnilang\Exception\InvalidArgumentException;
use Omnilang\Exception\InvalidConfigException;
use Omnilang\Http\Multipart;
use Omnilang\Model\Document;
use Omnilang\Model\DocumentJob;
use Omnilang\Model\DocumentQuery;
use Omnilang\Model\DocumentStatus;
use Omnilang\Model\Glossary;
use Omnilang\Model\Language;
use Omnilang\Model\LanguagePair;
use Omnilang\Model\SupportedLanguage;
use Omnilang\Model\TranslationQuery;
use Omnilang\Policy\GuardedTranslator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    /** @return iterable<array{string, string}> */
    public static function tags(): iterable
    {
        yield ['en', 'en'];
        yield ['EN', 'en'];
        yield ['en-gb', 'en-GB'];
        yield ['pt_BR', 'pt-BR'];
        yield ['ZH-HANS', 'zh-Hans'];
        yield ['sr-latn-rs', 'sr-Latn-RS'];
        yield ['es-419', 'es-419'];
    }

    #[DataProvider('tags')]
    public function testALanguageTagIsNormalised(string $given, string $normal): void
    {
        self::assertSame($normal, Language::of($given)->code);
    }

    public function testALanguagesParts(): void
    {
        $tag = Language::of('sr-Latn-RS');

        self::assertSame(['sr', 'Latn', 'RS'], [$tag->primary(), $tag->script(), $tag->region()]);
        self::assertTrue(Language::of('en-GB')->sameLanguage('en-US'));
        self::assertFalse(Language::of('en-GB')->is('en-US'));
        self::assertNull(Language::tryOf('auto?'));
        $this->expectException(InvalidArgumentException::class);
        Language::of('français');
    }

    public function testAQueryHoldsItsTextsAndGlossaries(): void
    {
        $query = new TranslationQuery(['Chambre double', 'Petit-déjeuner'], 'en-gb', 'FR', glossaries: ['deepl' => 'g-1']);

        self::assertSame(['en-GB', 'fr', 28], [$query->target->code, $query->source?->code, $query->characters()]);
        self::assertSame('g-1', $query->glossaryFor('deepl'));
        self::assertNull($query->glossaryFor('google'));
        self::assertSame('g-2', $query->withGlossaries('g-2')->glossaryFor('google'));
        self::assertTrue(TranslationQuery::html('<p>x</p>', 'ja')->isHtml());

        $this->expectException(InvalidArgumentException::class);
        new TranslationQuery([], 'en');
    }

    public function testAQueryIsCutIntoRequestsTheProviderTakes(): void
    {
        $query = TranslationQuery::text(['aaaa', 'bb', 'cccccc', 'd'], 'en');

        self::assertSame([['aaaa', 'bb'], ['cccccc'], ['d']], array_map(static fn (TranslationQuery $q) => $q->texts, GuardedTranslator::chunks($query, 2, 6)));
        self::assertSame([['aaaa', 'bb', 'cccccc', 'd']], array_map(static fn (TranslationQuery $q) => $q->texts, GuardedTranslator::chunks($query, null, null)));
        self::assertSame([['aaaa'], ['bb'], ['cccccc'], ['d']], array_map(static fn (TranslationQuery $q) => $q->texts, GuardedTranslator::chunks($query, 1, null)));
    }

    public function testGlossaryEntriesAsTabSeparatedValues(): void
    {
        $tsv = Glossary::tsv(['Chambre double' => 'Double room', "Petit\tdéjeuner" => "Break\nfast"]);

        self::assertSame("Chambre double\tDouble room\nPetit déjeuner\tBreak fast", $tsv);
        self::assertSame(['Chambre double' => 'Double room', 'Petit déjeuner' => 'Break fast'], Glossary::fromTsv($tsv."\r\nno tab here\n"));
        $glossary = new Glossary('g', 'Rooms', 'x', [LanguagePair::of('fr', 'en', 2), LanguagePair::of('fr', 'de', 3)]);
        self::assertSame(5, $glossary->entries());
        self::assertTrue($glossary->covers('fr', 'en-GB'));
        self::assertFalse($glossary->covers('en', 'fr'));
    }

    public function testADocumentAndItsJob(): void
    {
        $document = new Document('<p>Menu</p>', 'carte.html');
        $query = new DocumentQuery($document, 'ja');
        $job = new DocumentJob('id-1', 'deepl', DocumentStatus::Queued, $query->target, key: 'secret-document-key');

        self::assertSame(['text/html', 'html', 'carte.ja.html'], [$document->mimeType, $document->extension(), $query->translatedName()]);
        self::assertSame('application/octet-stream', (new Document('x', 'blob.unknown'))->mimeType);
        self::assertArrayNotHasKey('key', $job->toArray());
        self::assertStringNotContainsString('secret-document-key', json_encode($job->toArray(), \JSON_THROW_ON_ERROR));
        $done = $job->with(DocumentStatus::Done, billedCharacters: 50000);
        self::assertSame([true, 'secret-document-key', 50000], [$done->isDone(), $done->key, $done->billedCharacters]);
        self::assertNotNull(SupportedLanguage::find([new SupportedLanguage(Language::of('en-GB'))], 'en'));

        $this->expectException(InvalidArgumentException::class);
        new Document('x', '../etc/passwd');
    }

    public function testAMultipartBodyReadsBack(): void
    {
        $form = Multipart::encode(['target_lang' => 'JA', 'formality' => null, 'enable' => true], ['file' => new Document("Bonjour\r\n", 'menu.txt')]);
        $parts = Multipart::decode($form['headers']['Content-Type'], $form['body']);

        self::assertStringStartsWith('multipart/form-data; boundary=omnilang-', $form['headers']['Content-Type']);
        self::assertSame(['target_lang', 'enable', 'file'], array_keys($parts));
        self::assertSame(['value' => "Bonjour\r\n", 'filename' => 'menu.txt', 'type' => 'text/plain'], $parts['file']);
        self::assertSame('1', $parts['enable']['value']);
        self::assertSame($form, Multipart::encode(['target_lang' => 'JA', 'formality' => null, 'enable' => true], ['file' => new Document("Bonjour\r\n", 'menu.txt')]), 'the same request, the same bytes');
    }

    public function testAKeyGoesByReference(): void
    {
        $_ENV['OMNILANG_TEST_KEY'] = 'from-the-environment';
        $config = new Config(['key' => 'env:OMNILANG_TEST_KEY', 'given' => 'as-given', 'omnilang.factory_name' => 'deepl']);

        self::assertSame(['from-the-environment', 'as-given', null], [$config->secret('key'), $config->secret('given'), $config->secret('none')]);
        unset($_ENV['OMNILANG_TEST_KEY']);
        try {
            $config->secret('key');
            self::fail('A variable that is not set is said.');
        } catch (InvalidConfigException $e) {
            self::assertSame('The "deepl" provider\'s "key" option reads the environment variable OMNILANG_TEST_KEY, which is not set.', $e->getMessage());
        }
    }
}
