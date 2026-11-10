---
title: Providers
order: 30
---

# Providers

Every endpoint below was read in the engine's public documentation, or its machine-readable
description, on 2026-11-10; each package's `docs/index.md` says how every field is read.

| | DeepL | Google | Azure | Amazon | LibreTranslate |
|---|---|---|---|---|---|
| API | v2 translate, v3 languages and glossaries, v2 document | Cloud Translation - Advanced v3 | Translator text v3.0, document 2024-05-01 | Translate (awsJson 1.1, SigV4) | its server's (1.9.6) |
| Translate | yes | yes | yes | yes, one text a call | yes |
| HTML | yes (`tag_handling`) | yes (`text/html`) | yes (`textType`) | no (TranslateText) | yes (`format`) |
| Formality | preference (`prefer_more`/`prefer_less`) | no | no (v3.0) | `FORMAL` / `INFORMAL` | no |
| Detect | no (a translation says it) | yes | yes | no (`auto` says it) | yes |
| Glossaries | v3: make, list, read, apply, delete | list, read, apply, delete (made from Cloud Storage) | no | terminologies: import, list, read, apply, delete | no |
| Documents | async: docx, pptx, xlsx, pdf, html, txt, xliff, srt… | sync, regional: pdf, docx, pptx, xlsx | sync, custom domain: docx, pptx, xlsx, html, txt, csv, xlf, msg… | sync: html, txt, docx (100 KB, English on one side) | sync: txt, odt, odp, docx, pptx, epub, html, srt, pdf |
| Request | 128 KiB | 1,024 texts, ~30,000 code points | 1,000 texts, 50,000 characters | 10,000 bytes | the server's |
| Credentials | `key` (`DeepL-Auth-Key`) | `access_token` (OAuth Bearer) | `key` + `region` | `access_key_id` + `secret_access_key` (+ `session_token`) | `api_key` when the server asks |
| Attribution | required (DeepL's name and domain) | required ("powered by Google Translate") | not stated | not required | none |
| Data | kept as needed, 72 h for debugging; perpetual on the free Developer plan | in memory only, not for training | text not stored | may be stored and used to improve, unless opted out | the operator's |

The figures and their sources are in each package's `policy.json`.
