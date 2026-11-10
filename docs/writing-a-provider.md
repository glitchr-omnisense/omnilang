---
title: Writing a provider
order: 70
---

# Writing a provider

A package `omnilang/<name>` (namespace `Omnilang\<Name>\`, repository
`glitchr-omnisense/omnilang-<name>`), requiring `omnisense/omnilang`, `omnisense/contracts` and,
when it calls an API, `symfony/http-client`:

1. A factory extending `ProviderFactory`: `populate()` names it (`omnilang.factory_name`), says
   its required options (`omnilang.required_options`) and the defaults of the others; `build()`
   makes the provider, reading credentials with `Config::secret()` (by reference) and handing
   `Config::backoff()` to its `JsonClient`. Its constructor takes `?HttpClientInterface` and makes
   its own client only when given none.
2. The provider implements the contracts it keeps, and only those, and
   `DeclaresUsagePolicyInterface`; a call it cannot fully answer throws `NotSupportedException`,
   and `capabilities()` says so beforehand (HTML, formality, glossaries, document types, the
   texts and characters a request takes).
3. HTTP through `Http\JsonClient` (`get()`, `request()`, `binary()`; `Http\Multipart` for an
   upload): a 404 is `null`, other errors are exceptions without the key. A credential the request
   does not show (a signing secret) goes in its `$secrets`.
4. Read answers with `Http\Data`, never trusting their shape.
5. `policy.json` (omnisense/contracts' schema), named in `extra.omnisense.policy`: the limits,
   rights and attribution read in the engine's terms, the day they were read, the data retention
   and what was not found in its notes. Unknown stays `unknown`.
6. Tests on `Testing\MockHttp` asserting the exact requests (answers written from the engine's own
   documentation or schema), a `PolicyTest`, a `BareTest`, PHPStan at max with an empty baseline,
   `docs/index.md` (options, calls, how each field is read, verified and not).
7. Add it to `docker/harness/plugins.php` and to [Providers](providers.md).
