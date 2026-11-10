<?php

namespace Omnilang\Exception;

/** A provider asked for what it does not do: a detection of DeepL, HTML of Amazon's TranslateText, a glossary of LibreTranslate. */
final class NotSupportedException extends \LogicException implements OmnilangException
{
    public static function capability(object $provider, string $name, string $capability): self
    {
        return new self(\sprintf('The "%s" provider (%s) does not %s.', $name, $provider::class, $capability));
    }

    public static function call(string $provider, string $what): self
    {
        return new self(\sprintf('%s does not %s.', $provider, $what));
    }
}
