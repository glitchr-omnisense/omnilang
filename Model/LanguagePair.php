<?php

namespace Omnilang\Model;

/** A glossary's direction: its terms in $source, translated into $target; how many entries it holds, when said. */
final readonly class LanguagePair
{
    public function __construct(
        public Language $source,
        public Language $target,
        public ?int $entries = null,
    ) {
    }

    public static function of(string|Language $source, string|Language $target, ?int $entries = null): self
    {
        return new self(Language::of($source), Language::of($target), $entries);
    }

    public function matches(string|Language $source, string|Language $target): bool
    {
        return $this->source->sameLanguage($source) && $this->target->sameLanguage($target);
    }
}
