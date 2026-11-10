<?php

namespace Omnilang\Exception;

/** A value no query can hold: an empty text, a language tag that is none. Never a reason to fall back. */
final class InvalidArgumentException extends \InvalidArgumentException implements OmnilangException
{
}
