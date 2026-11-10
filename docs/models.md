---
title: Models
order: 20
---

# Models

| Model | What |
|---|---|
| `Language` | a BCP 47 tag, normalised (`en`, `en-GB`, `pt-BR`, `zh-Hans`; `pt_BR` and `EN-us` read too): `primary()`, `script()`, `region()`, `is()`, `sameLanguage()`. Each engine writes it its way (DeepL `EN-GB`) and reads its answers back (`EN` is `en`). |
| `TranslationQuery` | one text or several, into one language, from one or from whatever it is (`null`: detected); `TextFormat::Plain` or `Html`; a `Formality` (`Formal`, `Informal`) as a preference; glossary ids by provider name (`['deepl' => '…']`; a lone id is `*`, for the one engine called). `text()`, `html()`, `characters()`. |
| `Translation` | the text translated, its target, the source given, the source `detected`, the engine (`provider`), the `characters` it counted (DeepL's billed characters; the text's length elsewhere, null where unknown), the glossary applied. `sourceLanguage()`: given, else detected. |
| `SupportedLanguage` | a language an engine translates from (`source`) and into (`target`), its name, `formality` where said; `find()` by tag then by language. |
| `Detection` | a language, a `confidence` from 0 to 1 (null when the engine gives none), the `alternatives`. |
| `Glossary`, `LanguagePair` | the engine's id, a name, its directions with their entry counts, when it was made; `tsv()` / `fromTsv()`. |
| `Document` | bytes, a file name, a media type (from the extension when not given); `fromFile()`. |
| `DocumentQuery` | a document, a target, a source, a formality, glossaries; `translatedName()` (`menu.docx` into Japanese is `menu.ja.docx`). |
| `DocumentJob`, `DocumentStatus` | queued, translating, done, error; the engine's id and its secret `key` (DeepL's `document_key`, left out of `toArray()`); the result, at once for a synchronous engine. |
