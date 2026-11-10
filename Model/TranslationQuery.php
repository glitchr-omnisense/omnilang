<?php

namespace Omnilang\Model;

use Omnilang\Exception\InvalidArgumentException;

/**
 * What to translate: one text or several (each translated on its own, in
 * order), into one language, from one language or from whatever it is
 * (null: the engine detects it). Plain text or HTML, a register, and the
 * glossaries to apply - by the provider's configured name, since a
 * glossary lives in one engine ("*": whichever is called).
 *
 *   TranslationQuery::text('Bonjour tout le monde', 'en', from: 'fr')
 *   TranslationQuery::html('<p>Réservez <b>maintenant</b></p>', 'ja', formality: Formality::Formal)
 *   new TranslationQuery(['Chambre double', 'Petit-déjeuner'], 'en-GB', glossaries: ['deepl' => 'def3a26b-…'])
 */
final readonly class TranslationQuery
{
    /** @var list<string> */
    public array $texts;

    public Language $target;

    public ?Language $source;

    /** @var array<string, string> glossary id by provider name ("*": any) */
    public array $glossaries;

    /**
     * @param string|array<mixed>      $texts      one text, or a list of them
     * @param string|array<mixed>|null $glossaries an id for whichever provider is called, or ids by provider name
     */
    public function __construct(
        string|array $texts,
        string|Language $target,
        string|Language|null $source = null,
        public TextFormat $format = TextFormat::Plain,
        public ?Formality $formality = null,
        string|array|null $glossaries = null,
    ) {
        $list = [];
        foreach (\is_string($texts) ? [$texts] : $texts as $text) {
            $list[] = \is_string($text) ? $text : throw new InvalidArgumentException(\sprintf('A text to translate is a string, %s given.', get_debug_type($text)));
        }
        if ([] === $list) {
            throw new InvalidArgumentException('A translation needs a text.');
        }
        $this->texts = $list;
        $this->target = Language::of($target);
        $this->source = null === $source ? null : Language::of($source);
        $this->glossaries = self::glossaryIds($glossaries);
    }

    /**
     * Glossary ids by provider name, from one id ("*": any provider) or a map; empty ids left out.
     *
     * @param string|array<mixed>|null $glossaries
     *
     * @return array<string, string>
     */
    public static function glossaryIds(string|array|null $glossaries): array
    {
        if (\is_string($glossaries)) {
            return '' === $glossaries ? [] : ['*' => $glossaries];
        }
        $ids = [];
        foreach ($glossaries ?? [] as $provider => $id) {
            if (\is_string($id) && '' !== $id) {
                $ids[(string) $provider] = $id;
            }
        }

        return $ids;
    }

    /** @param string|list<string> $texts */
    public static function text(string|array $texts, string|Language $to, string|Language|null $from = null, ?Formality $formality = null): self
    {
        return new self($texts, $to, $from, TextFormat::Plain, $formality);
    }

    /** @param string|list<string> $html */
    public static function html(string|array $html, string|Language $to, string|Language|null $from = null, ?Formality $formality = null): self
    {
        return new self($html, $to, $from, TextFormat::Html, $formality);
    }

    /** The glossary to apply when this provider is called: its own, else the one for any. */
    public function glossaryFor(string $provider): ?string
    {
        return $this->glossaries[$provider] ?? $this->glossaries['*'] ?? null;
    }

    /** @param string|array<string, string> $glossaries */
    public function withGlossaries(string|array $glossaries): self
    {
        return new self($this->texts, $this->target, $this->source, $this->format, $this->formality, $glossaries);
    }

    /** @param list<string> $texts */
    public function withTexts(array $texts): self
    {
        return new self($texts, $this->target, $this->source, $this->format, $this->formality, $this->glossaries);
    }

    /** The characters of every text, as engines count them (Unicode code points). */
    public function characters(): int
    {
        return array_sum(array_map('mb_strlen', $this->texts));
    }

    public function isHtml(): bool
    {
        return TextFormat::Html === $this->format;
    }
}
