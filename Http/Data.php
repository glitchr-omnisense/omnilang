<?php

namespace Omnilang\Http;

/**
 * A decoded JSON answer, read by key without trusting its shape: each
 * reader gives its type or null (an empty list), whatever the provider sent.
 * omnisense/contracts' Data, under omnilang's name: every reader gives one
 * of these back.
 */
final readonly class Data extends \Omnisense\Contracts\Http\Data
{
}
