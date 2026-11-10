<?php

namespace Omnilang\Model;

/**
 * One text translated: the translation, the language it is in, the source
 * language the query gave or the one the engine detected ($detected, when
 * the query gave none), who translated it, and the characters it counted
 * against the engine's quota (the engine's own count where it says it -
 * DeepL's billed_characters, Azure's X-metered-usage spread over the texts
 * - the text's length otherwise).
 */
final readonly class Translation implements \Stringable
{
    public function __construct(
        public string $text,
        public Language $target,
        public string $provider,
        public ?Language $source = null,
        public ?Language $detected = null,
        public TextFormat $format = TextFormat::Plain,
        public ?int $characters = null,
        public ?string $glossary = null,
    ) {
    }

    /** The language the text was in: the one given, else the one detected. */
    public function sourceLanguage(): ?Language
    {
        return $this->source ?? $this->detected;
    }

    public function __toString(): string
    {
        return $this->text;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'text' => $this->text, 'target' => $this->target->code, 'source' => $this->source?->code, 'detected' => $this->detected?->code,
            'format' => $this->format->value, 'provider' => $this->provider, 'characters' => $this->characters, 'glossary' => $this->glossary,
        ];
    }
}
