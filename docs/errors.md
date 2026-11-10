---
title: Errors
order: 60
---

# Errors

Every exception implements `Omnilang\Exception\OmnilangException` (and omnisense/contracts'
`ExceptionInterface`).

| Exception | When | Fallback |
|---|---|---|
| `InvalidConfigException` | an engine not configured, a factory not installed, a required option empty, an `env:` variable not set | thrown at once |
| `InvalidArgumentException` | a value no query can hold: no text, a tag that is no language, a lone glossary id with several engines to fall back on | thrown at once |
| `NotSupportedException` | a contract or a call the engine does not keep (DeepL's detection, Amazon's HTML, a glossary without a source language) | passed over |
| `ProviderException` | the engine answered an error (`$status`: DeepL's 456, Google's 403, Amazon's 400 with its error's name) or an answer that is not what its API documents | passed over |
| `UnreachableException` | the engine could not be reached | passed over |
| `UsageLimitReachedException` | a limit of the policy reached, or the engine asked to wait (`retryAfter`) | passed over |
| `FallbackFailedException` | no engine of the order could; `$failures` by name | - |

A message names the method and the path called, never the query, the body or the headers; neither
the exception nor its previous one keeps a key, a token, a signing secret or a document key, in its
message or in its trace's arguments (`Http\Redaction`, and `JsonClient`'s own secrets). An engine
answering 404 for a record gives `null` or an empty list.
