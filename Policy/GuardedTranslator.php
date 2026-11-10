<?php

namespace Omnilang\Policy;

use Omnilang\Model\TranslationQuery;
use Omnilang\TranslatorInterface;
use Omnisense\Contracts\Policy\PolicyGuard;

/**
 * A provider's translations behind its guard: each call is the operation
 * "translate" or "languages". A translation reports its characters (Unicode
 * code points, as engines bill them) to a limit counted in "characters"
 * (`"unit": "characters"`); a limit in requests counts the request. A query holding more texts or characters
 * than one request of the provider may (Capabilities::$maxTexts,
 * $maxCharacters) is sent in as many requests as it takes, each one
 * guarded - a text longer than a request alone goes on its own, for the
 * provider to refuse.
 *
 * @extends Guarded<TranslatorInterface>
 */
final class GuardedTranslator extends Guarded implements TranslatorInterface
{
    public function __construct(TranslatorInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function translate(TranslationQuery $query): array
    {
        $capabilities = $this->provider->capabilities();
        $translations = [];
        foreach (self::chunks($query, $capabilities->maxTexts, $capabilities->maxCharacters) as $chunk) {
            array_push($translations, ...$this->guard->call('translate', fn () => $this->provider->translate($chunk), amounts: ['characters' => $chunk->characters()]));
        }

        return $translations;
    }

    public function languages(): array
    {
        return $this->guard->call('languages', fn () => $this->provider->languages());
    }

    /**
     * The query cut into requests of at most $maxTexts texts and
     * $maxCharacters characters, the texts' order kept.
     *
     * @return list<TranslationQuery>
     */
    public static function chunks(TranslationQuery $query, ?int $maxTexts, ?int $maxCharacters): array
    {
        if ((null === $maxTexts || \count($query->texts) <= $maxTexts) && (null === $maxCharacters || $query->characters() <= $maxCharacters)) {
            return [$query];
        }
        $chunks = $current = [];
        $characters = 0;
        foreach ($query->texts as $text) {
            $length = mb_strlen($text);
            if ([] !== $current && ((null !== $maxTexts && \count($current) >= $maxTexts) || (null !== $maxCharacters && $characters + $length > $maxCharacters))) {
                $chunks[] = $query->withTexts($current);
                $current = [];
                $characters = 0;
            }
            $current[] = $text;
            $characters += $length;
        }
        $chunks[] = $query->withTexts($current);

        return $chunks;
    }
}
