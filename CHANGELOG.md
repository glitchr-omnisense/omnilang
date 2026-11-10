# Changelog

## 1.x (unreleased)

- 2026-11-10: the family written, on omnisense/contracts from its first commit. Four contracts:
  `TranslatorInterface` (translate, languages), `DetectorInterface`, `GlossaryInterface`,
  `DocumentsInterface`. Models: `Language` (BCP 47, normalised), `TranslationQuery` (texts, target,
  source, `TextFormat`, `Formality` as a preference, glossaries by provider name), `Translation`
  (with the detected source and the characters counted), `SupportedLanguage`, `Detection`,
  `Glossary` and `LanguagePair` (TSV in and out), `Document`, `DocumentQuery`, `DocumentJob`,
  `DocumentStatus`. `Capabilities` by contract (HTML, formality, glossaries in a translation,
  documents and their types, the texts and characters a request holds) with the provider's
  `UsagePolicy`. `Registry`: `translator()`, `detector()`, `glossary()`, `documents()` behind each
  provider's guard (Policy\Guarded*, a query cut to the provider's request size, each request
  counted), `translate()`, `translateText()`, `detect()`, `translateDocument()` down a fallback
  order (`FallbackFailedException`). `Config::secret()`: keys by reference (`env:NAME`).
  `Http\JsonClient` with `binary()` and secrets masked beyond the request's, `Http\Multipart`.
  Testing: `FixedProvider`, `MockHttp` (Symfony's MockHttpClient, every request kept as sent),
  `RecordedHttpClient`, `BareTestCase`. The docker harness, `docs/`. Providers: omnilang/deepl,
  omnilang/google, omnilang/azure, omnilang/amazon, omnilang/libretranslate.
