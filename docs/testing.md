---
title: Testing
order: 40
---

# Testing

- `Testing\FixedProvider` (factory `fixed`, or the name given): keeps all four contracts,
  answering from a dictionary by target language, detections by text, glossaries in memory,
  documents line by line; a text it does not hold is `NotSupportedException`, so that a registry
  falls back past it. Options `html` (false: plain text only) and `max_texts`. An application's
  tests configure it in place of a real engine.
- `Testing\MockHttp`: Symfony's `MockHttpClient` answering by route (`'~^POST
  https://api-free\.deepl\.com/v2/translate$~' => [200, '@/path/answer.json']`, or a closure of the
  method, URL and body) and keeping every request as it went on the wire - `lines()`, `header()`,
  `json()`, `form()` (multipart), `dump()` - so that a provider's test asserts the exact request
  and that no key travels or leaks. It needs `symfony/http-client` (a test dependency).
- `Testing\RecordedHttpClient`: the kernel's recorder, without symfony/http-client.
- `Testing\BareTestCase`: a package's classes loaded in a PHP process of their own, with nothing
  from outside but the core, the kernel and what the package requires: no framework, no HTTP
  client.

Every engine but LibreTranslate needs credentials, and no public LibreTranslate server says it may
be called by a program without a key: the tests make no live call. Start a server of your own
(`docker run -p 5000:5000 libretranslate/libretranslate`) to try one.
