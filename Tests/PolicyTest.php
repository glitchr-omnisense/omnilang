<?php

namespace Omnilang\Tests;

use Omnilang\Exception\FallbackFailedException;
use Omnilang\Exception\UsageLimitReachedException;
use Omnilang\Model\TranslationQuery;
use Omnilang\Registry;
use Omnilang\Testing\FixedProviderFactory;
use Omnisense\Contracts\Exception\ExceptionInterface;
use Omnisense\Contracts\Policy\InvalidPolicyException;
use Omnisense\Contracts\Policy\InMemoryRateLimiter;
use Omnisense\Contracts\Policy\UsageNotPermitted;
use PHPUnit\Framework\TestCase;

/** A site's limits on an engine, counted per operation, and the fallback past an engine over its limit. */
final class PolicyTest extends TestCase
{
    public function testASitesLimitIsEnforcedPerOperation(): void
    {
        $registry = self::registry(['a' => ['policy' => ['rateLimits' => [['max' => 1, 'window' => 'day', 'operations' => ['translate']]]]]]);

        $registry->translator('a')->translate(TranslationQuery::text('Bonjour', 'en'));
        $registry->translator('a')->languages();
        $registry->translator('a')->languages();
        try {
            $registry->translator('a')->translate(TranslationQuery::text('Bonjour', 'en'));
            self::fail('The second translation of the day is refused.');
        } catch (UsageLimitReachedException $e) {
            self::assertInstanceOf(ExceptionInterface::class, $e);
            self::assertGreaterThan(0, $e->retryAfter);
        }
    }

    public function testAnEngineOverItsLimitHandsOverToTheNext(): void
    {
        $registry = self::registry(['a' => ['policy' => ['rateLimits' => [['max' => 1, 'window' => 'hour']]]]]);

        self::assertSame('a', $registry->translateText('Bonjour', 'en')->provider);
        self::assertSame('b', $registry->translateText('Bonjour', 'en')->provider);
    }

    public function testEachRequestOfACutQueryCounts(): void
    {
        $registry = self::registry(['b' => ['max_texts' => 1, 'policy' => ['rateLimits' => [['max' => 3, 'window' => 'minute', 'operations' => ['translate']]]]]]);
        $texts = array_fill(0, 3, 'Bonjour');

        self::assertCount(2, $registry->translator('b')->translate(TranslationQuery::text(array_slice($texts, 0, 2), 'en')));
        self::assertSame(1, $registry->guard('b')->remaining('translate'));
        $this->expectException(FallbackFailedException::class);
        $registry->translate(TranslationQuery::text(['Bonjour', 'Bonjour'], 'en'), ['b']);
    }

    public function testASiteMayTightenAPolicyNeverLoosenIt(): void
    {
        $policy = self::registry(['a' => ['policy' => ['rights' => ['commercial' => 'forbidden']]]])->policy('a');
        self::assertSame('forbidden', $policy->rights->commercial->value);

        $this->expectException(InvalidPolicyException::class);
        $policy->tighten(['rights' => ['commercial' => 'allowed']]);
    }

    public function testACommercialSiteIsRefusedAnEngineWhoseTermsForbidIt(): void
    {
        $registry = self::registry(['a' => ['policy' => ['rights' => ['commercial' => 'forbidden']], 'usage' => ['commercial' => true]]]);

        $this->expectException(UsageNotPermitted::class);
        $registry->translator('a');
    }

    /** @param array<string, array<string, mixed>> $options */
    private static function registry(array $options): Registry
    {
        return new Registry([
            new FixedProviderFactory(['en' => ['Bonjour' => 'Hello']], [], 'a'),
            new FixedProviderFactory(['en' => ['Bonjour' => 'Good morning']], [], 'b'),
        ], ['a' => ['factory' => 'a', 'options' => $options['a'] ?? []], 'b' => ['factory' => 'b', 'options' => $options['b'] ?? []]], new InMemoryRateLimiter());
    }
}
