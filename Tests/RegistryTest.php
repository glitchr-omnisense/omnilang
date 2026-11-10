<?php

namespace Omnilang\Tests;

use Omnilang\Exception\FallbackFailedException;
use Omnilang\Exception\InvalidArgumentException;
use Omnilang\Exception\InvalidConfigException;
use Omnilang\Exception\NotSupportedException;
use Omnilang\Model\Document;
use Omnilang\Model\DocumentQuery;
use Omnilang\Model\Language;
use Omnilang\Model\TranslationQuery;
use Omnilang\Policy\GuardedTranslator;
use Omnilang\Registry;
use Omnilang\Testing\FixedProvider;
use Omnilang\Testing\FixedProviderFactory;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    public function testTheFallbackOrderIsTheRegistrysThenTheConfigurations(): void
    {
        $registry = self::registry(['b', 'a']);

        self::assertSame(['b', 'a'], $registry->fallbackOrder());
        self::assertSame(['a'], $registry->fallbackOrder('translate', ['a']));
        self::assertSame(['a', 'b'], self::registry()->fallbackOrder());
        self::assertSame(['translate', 'detect', 'glossary', 'documents'], $registry->get('a')->capabilities()->contracts());
    }

    public function testATextNoEngineKnowsIsPassedOnThenEveryFailureIsSaid(): void
    {
        $registry = self::registry();

        self::assertSame(['Hello', 'a'], [$registry->translateText('Bonjour', 'en')->text, $registry->translateText('Bonjour', 'en')->provider]);
        self::assertSame(['Good evening', 'b'], [$registry->translateText('Bonsoir', 'en')->text, $registry->translateText('Bonsoir', 'en')->provider], 'a knows it not, b does');
        try {
            $registry->translateText('Salut', 'en');
            self::fail('No engine knows "Salut".');
        } catch (FallbackFailedException $e) {
            self::assertSame(['a', 'b'], array_keys($e->failures));
            self::assertInstanceOf(NotSupportedException::class, $e->getPrevious());
            self::assertStringContainsString('a: a does not hold a translation of "Salut" into en.', $e->getMessage());
        }
    }

    public function testHtmlGoesToAnEngineThatKeepsTags(): void
    {
        $registry = self::registry(options: ['a' => ['html' => false]]);
        $translation = $registry->translate(TranslationQuery::html('<p>Merci</p>', 'en'))[0];

        self::assertSame(['<p>Thank you</p>', 'b'], [$translation->text, $translation->provider]);
    }

    public function testAGlossaryBelongsToOneEngine(): void
    {
        $registry = self::registry();
        $glossary = $registry->glossary('b')->createGlossary('Rooms', Language::of('fr'), Language::of('en'), ['Bonjour' => 'Welcome']);
        $translation = $registry->translate(TranslationQuery::text('Bonjour', 'en')->withGlossaries(['b' => $glossary->id]))[0];

        self::assertSame(['Hello', 'a'], [$translation->text, $translation->provider], 'a glossary named for b binds b only');
        $translation = $registry->translate(TranslationQuery::text('Bonjour', 'en')->withGlossaries(['b' => $glossary->id]), ['b', 'a'])[0];
        self::assertSame(['Welcome', 'b'], [$translation->text, $translation->provider]);
        $unknown = $registry->translate(TranslationQuery::text('Bonjour', 'en')->withGlossaries(['a' => 'no-such-glossary']))[0];
        self::assertSame('b', $unknown->provider, 'a knows no such glossary: passed over');
        self::assertSame(['Bonjour' => 'Welcome'], $registry->glossary('b')->glossaryEntries($glossary->id, Language::of('fr'), Language::of('en')));
        self::assertSame('Welcome', $registry->translate(TranslationQuery::text('Bonjour', 'en')->withGlossaries($glossary->id), ['b'])[0]->text, 'one engine: "*" is its own');

        $this->expectException(InvalidArgumentException::class);
        $registry->translate(TranslationQuery::text('Bonjour', 'en')->withGlossaries($glossary->id));
    }

    public function testDetectionAndDocumentsFallBackToo(): void
    {
        $registry = self::registry();

        self::assertSame(['de', 'b'], [$registry->detect('Guten Morgen')[0]->language->code, $registry->detect('Guten Morgen')[0]->provider]);
        $job = $registry->translateDocument(new DocumentQuery(new Document("Bonjour\nBonsoir", 'note.txt'), 'en'));
        self::assertSame(['a', "Hello\nBonsoir", 'note.en.txt'], [$job->provider, $job->result?->content, $job->result?->filename]);
        try {
            $registry->translateDocument(new DocumentQuery(new Document('x', 'deck.pptx'), 'en'));
            self::fail('No engine reads .pptx.');
        } catch (FallbackFailedException $e) {
            self::assertStringContainsString('a does not translate a ".pptx" document.', $e->getMessage());
        }
    }

    public function testEachContractIsGuardedAndTheProviderItselfIsNot(): void
    {
        $registry = self::registry();

        self::assertInstanceOf(GuardedTranslator::class, $registry->translator('a'));
        self::assertInstanceOf(FixedProvider::class, $registry->get('a'));
        self::assertSame($registry->translator('a'), $registry->translator('a'));
    }

    public function testAWrongConfigurationIsSaidAtOnce(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The fallback order names "c", which is not configured; configured: a, b.');
        self::registry(['a', 'c']);
    }

    /**
     * @param list<string>                        $fallback
     * @param array<string, array<string, mixed>> $options
     */
    private static function registry(array $fallback = [], array $options = []): Registry
    {
        return new Registry([
            new FixedProviderFactory(['en' => ['Bonjour' => 'Hello']], [], 'a'),
            new FixedProviderFactory(['en' => ['Bonjour' => 'Good morning', 'Bonsoir' => 'Good evening', '<p>Merci</p>' => '<p>Thank you</p>']], ['Guten Morgen' => 'de'], 'b'),
        ], ['a' => ['factory' => 'a', 'options' => $options['a'] ?? []], 'b' => ['factory' => 'b']], null, $fallback);
    }
}
