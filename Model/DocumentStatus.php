<?php

namespace Omnilang\Model;

/** Where a document's translation is: DeepL's queued, translating, done, error; a synchronous engine's job is done at once. */
enum DocumentStatus: string
{
    case Queued = 'queued';
    case Translating = 'translating';
    case Done = 'done';
    case Error = 'error';

    public function isFinished(): bool
    {
        return self::Done === $this || self::Error === $this;
    }
}
