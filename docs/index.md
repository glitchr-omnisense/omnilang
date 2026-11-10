---
title: Omnilang
order: 0
---

# Omnilang

The question the family answers: **what does this text say in another language, whichever engine
translates it?** Texts and pages, the language a text is in, glossaries, whole documents;
framework-free, MIT.

- [Installation](installation.md): plain PHP first, then the frameworks.
- [Symfony](symfony.md): the registry as a service, keys from the vault.
- [Models](models.md): languages, queries, translations, glossaries, documents.
- [Providers](providers.md): what each engine keeps, its options, its terms.
- [Usage policies](policies.md): limits, attribution, data retention; the fallback.
- [Testing](testing.md): `FixedProvider`, `MockHttp`, the bare check.
- [The harness](harness.md): every package of the family in one container.
- [Errors](errors.md).
- [Writing a provider](writing-a-provider.md).

The boundaries: a site's own message catalogues and their translation platforms (Crowdin,
Lokalise, Phrase) are Symfony's translation providers'; a translation by a language model is
Symfony AI's; omnilang translates content with machine-translation engines.
