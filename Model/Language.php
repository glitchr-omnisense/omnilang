<?php

namespace Omnilang\Model;

use Omnilang\Exception\InvalidArgumentException;

/**
 * A language as BCP 47 writes it, normalised: the language in lower case,
 * a script in title case, a region in upper case ("en", "en-GB", "pt-BR",
 * "zh-Hans", "sr-Latn-RS"); "pt_BR" and "EN-us" read too. Each provider
 * writes it as its API wants (DeepL "EN-GB", Amazon "fr-CA") and reads
 * back what it answers ("EN" becomes "en").
 */
final readonly class Language implements \Stringable
{
    public string $code;

    private function __construct(string $code)
    {
        $this->code = $code;
    }

    /** @throws InvalidArgumentException when it is no language tag */
    public static function of(string|self $code): self
    {
        if ($code instanceof self) {
            return $code;
        }
        $tag = str_replace('_', '-', trim($code));
        if (!preg_match('~^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{1,8})*$~', $tag)) {
            throw new InvalidArgumentException(\sprintf('"%s" is no language tag (BCP 47: "en", "en-GB", "zh-Hans").', $code));
        }
        $parts = explode('-', $tag);
        $normal = [strtolower(array_shift($parts))];
        foreach ($parts as $i => $part) {
            $normal[] = match (true) {
                4 === \strlen($part) && ctype_alpha($part) && 0 === $i => ucfirst(strtolower($part)),
                2 === \strlen($part) && ctype_alpha($part), 3 === \strlen($part) && ctype_digit($part) => strtoupper($part),
                default => strtolower($part),
            };
        }

        return new self(implode('-', $normal));
    }

    /** A language from a provider's answer; null when it is empty or no tag. */
    public static function tryOf(mixed $code): ?self
    {
        if (!\is_string($code) || '' === trim($code)) {
            return null;
        }
        try {
            return self::of($code);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /** The language alone: "en" of "en-GB". */
    public function primary(): string
    {
        return explode('-', $this->code, 2)[0];
    }

    /** The script, when it is written: "Hans" of "zh-Hans". */
    public function script(): ?string
    {
        foreach (\array_slice(explode('-', $this->code), 1) as $part) {
            if (4 === \strlen($part) && ctype_alpha($part)) {
                return $part;
            }
        }

        return null;
    }

    /** The region, when it is written: "GB" of "en-GB", "419" of "es-419". */
    public function region(): ?string
    {
        foreach (\array_slice(explode('-', $this->code), 1) as $part) {
            if ((2 === \strlen($part) && ctype_alpha($part)) || (3 === \strlen($part) && ctype_digit($part))) {
                return $part;
            }
        }

        return null;
    }

    /** The same tag. */
    public function is(string|self $other): bool
    {
        return self::of($other)->code === $this->code;
    }

    /** The same language, whatever the region or script: "en-GB" and "en-US". */
    public function sameLanguage(string|self $other): bool
    {
        return self::of($other)->primary() === $this->primary();
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
