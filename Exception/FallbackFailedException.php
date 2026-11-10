<?php

namespace Omnilang\Exception;

/**
 * Every provider of a fallback order failed: $failures holds each one's
 * exception by its configured name, in the order they were tried; the last
 * one is the previous exception.
 */
final class FallbackFailedException extends \RuntimeException implements OmnilangException
{
    /** @var array<string, \Throwable> */
    public readonly array $failures;

    /** @param array<string, \Throwable> $failures */
    public function __construct(string $what, array $failures)
    {
        $this->failures = $failures;
        $last = [] === $failures ? null : $failures[array_key_last($failures)];
        $lines = array_map(static fn (string $name, \Throwable $e) => $name.': '.$e->getMessage(), array_keys($failures), $failures);
        parent::__construct(\sprintf('No provider could %s%s', $what, [] === $lines ? ': none keeps the contract.' : ' - '.implode(' | ', $lines)), 0, $last);
    }
}
