<?php

namespace Omnilang;

use Omnilang\Exception\InvalidConfigException;

/**
 * A provider's configuration while its factory builds it - omnisense/contracts'
 * Config, with omnilang's prefix ("omnilang.factory_name",
 * "omnilang.backoff"...) and its InvalidConfigException. Every provider
 * takes two options besides its own: "policy" (the site's tightening of its
 * usage policy) and "usage" (what the site says of itself: commercial,
 * open_source, contract).
 *
 * Keys go by reference: secret() reads an option that names where the
 * credential is ("env:DEEPL_API_KEY", read from the environment when the
 * provider is built) as well as the credential itself, given by the
 * application from its own vault ("%env(DEEPL_API_KEY)%" in Symfony). A
 * configuration file never holds a key; an error never quotes one.
 */
final class Config extends \Omnisense\Contracts\Provider\Config
{
    /** @var string */
    public const PREFIX = 'omnilang';

    /**
     * A credential option: "env:NAME" read from the environment ($_ENV,
     * $_SERVER, getenv()), anything else as given; null when it is empty.
     *
     * @throws InvalidConfigException when the variable named is not set (its name is said, never a value)
     */
    public function secret(string $key): ?string
    {
        $value = $this->string($key);
        if (null === $value || !str_starts_with($value, 'env:')) {
            return $value;
        }
        $name = substr($value, 4);
        if (!preg_match('~^[A-Za-z_][A-Za-z0-9_]*$~', $name)) {
            throw $this->invalid(\sprintf('The "%s" provider\'s "%s" option names no environment variable ("env:NAME").', $this->string($this->key('factory_name')) ?? '?', $key));
        }
        $found = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
        if (!\is_string($found) || '' === trim($found)) {
            throw $this->invalid(\sprintf('The "%s" provider\'s "%s" option reads the environment variable %s, which is not set.', $this->string($this->key('factory_name')) ?? '?', $key, $name));
        }

        return trim($found);
    }

    protected function invalid(string $message): \Throwable
    {
        return new InvalidConfigException($message);
    }
}
