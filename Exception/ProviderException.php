<?php

namespace Omnilang\Exception;

/**
 * The provider answered, and not with what was asked: an error status
 * ($status: DeepL's 456 when its character quota is spent, Google's 403
 * "User Rate Limit Exceeded", Amazon's 400 with its error's name), a body
 * that is not what its API documents (200).
 */
final class ProviderException extends \RuntimeException implements OmnilangException
{
    public ?int $status = null;

    public static function answered(string $provider, string $what, int|string $status, string $detail = ''): self
    {
        $e = new self(rtrim(\sprintf('%s answered %s for %s. %s', $provider, (string) $status, $what, mb_substr(trim(strip_tags($detail)), 0, 300))));
        $e->status = \is_int($status) ? $status : null;

        return $e;
    }

    /** The provider's answer is not the shape its API documents. */
    public static function unexpected(string $provider, string $what, string $detail): self
    {
        return self::answered($provider, $what, 200, $detail);
    }
}
