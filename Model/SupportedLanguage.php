<?php

namespace Omnilang\Model;

/**
 * A language an engine translates: from it ($source), into it ($target),
 * with a register to choose ($formality, null when the engine does not
 * say), its name as the engine gives it.
 */
final readonly class SupportedLanguage
{
    public function __construct(
        public Language $language,
        public ?string $name = null,
        public bool $source = true,
        public bool $target = true,
        public ?bool $formality = null,
    ) {
    }

    /**
     * The language of a list, by tag: the same tag first, then the same
     * language ("en" finds "en-GB" when no "en" is listed).
     *
     * @param list<self> $languages
     */
    public static function find(array $languages, string|Language $language): ?self
    {
        $language = Language::of($language);
        foreach ($languages as $one) {
            if ($one->language->is($language)) {
                return $one;
            }
        }
        foreach ($languages as $one) {
            if ($one->language->sameLanguage($language)) {
                return $one;
            }
        }

        return null;
    }
}
