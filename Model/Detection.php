<?php

namespace Omnilang\Model;

/**
 * The language a text is in, as an engine says it, and how sure it is:
 * $confidence from 0 to 1 (LibreTranslate's 0-100 brought to it), null
 * when the engine gives none. $alternatives: the other languages it
 * thought of, the likeliest first.
 */
final readonly class Detection
{
    /** @param list<self> $alternatives */
    public function __construct(
        public Language $language,
        public string $provider,
        public ?float $confidence = null,
        public array $alternatives = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['language' => $this->language->code, 'confidence' => $this->confidence, 'provider' => $this->provider, 'alternatives' => array_map(static fn (self $d) => $d->toArray(), $this->alternatives)];
    }
}
