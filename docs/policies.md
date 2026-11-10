---
title: Usage policies
order: 35
---

# Usage policies

Each provider ships a `policy.json` (omnisense/contracts' schema), named in its composer.json
(`"extra": {"omnisense": {"policy": "policy.json"}}`) and returned by its `usagePolicy()`. It
says:

- the **rate limits** the engine publishes, counted by operation - the contracts' method names:
  `translate`, `languages`, `detect`, `createGlossary`, `glossaries`, `glossary`,
  `glossaryEntries`, `deleteGlossary`, `translateDocument`, `documentStatus`, `documentResult`.
  `null`: none published (DeepL); `[]`: none on requests (Azure counts characters, a
  self-hosted LibreTranslate has none unless its operator sets some);
- the **rights**: commercial use, caching, storage, redistribution, the **attribution** to show
  beside a translation (DeepL's and Google's are required);
- the **requirements**: a key;
- the **provenance**: the pages it was read from, the day, and notes - the quotas by plan or tier,
  which the engine cannot declare for every site, the **data retention** (what the engine keeps of the texts, and for
  how long), what was not found.

Unknown stays unknown: a right the pages read do not state is `unknown`, never guessed.

## In the registry

Every call through `translator()`, `detector()`, `glossary()`, `documents()` - and so through the
fallback - is checked against the policy as the site runs it (the provider's, tightened by the
site's `policy`), then counted; `UsageLimitReachedException` (with `retryAfter`) refuses a call
over a limit or while the engine asks to wait (a 429 and its `Retry-After`). A query of more texts
or characters than one request of the engine takes is cut, each request counted.

```php
'deepl' => [
    'factory' => 'deepl',
    'options' => ['key' => 'env:DEEPL_API_KEY'],
    'policy' => ['rateLimits' => [['max' => 2000, 'window' => 'day', 'operations' => ['translate']]]],
    'usage' => ['commercial' => true],
],
```

A site tightens, never loosens: a higher limit, a right given back, an attribution dropped throws
`InvalidPolicyException`. `usage` says what the site is: a commercial site on an engine whose terms
forbid it is refused when the engine is first used (`UsageNotPermitted`).

## Quotas in characters

Engines sell characters, not calls. A limit counted in characters says so with `"unit":
"characters"`: each request of `translate` reports its texts' characters (Unicode code points,
whitespace included, as the engines bill them) and each `detect` its texts', so the guard refuses
a request whose characters are over what is left, before it is sent. A limit in requests (the
default unit) still counts the request. Google declares its 6,000,000 characters a minute this way;
a plan's quota (DeepL's, Azure's tier) is the site's to declare:

```php
'policy' => ['rateLimits' => [['max' => 500000, 'window' => 'month', 'unit' => 'characters', 'operations' => ['translate']]]],
```

Documents are billed by the engine once translated, not by the texts sent: they are counted in
requests only. `Translation::$characters` gives what each translation counted (DeepL's billed
characters), for a site that meters its own spending. A
spent quota answers an error the fallback passes over: DeepL's 456, Google's 403. Amazon, paid by
the character, has no quota to spend; its throttling answers 400 (`TooManyRequestsException`),
passed over too.
