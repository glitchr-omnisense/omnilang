---
title: Symfony
order: 15
---

# Symfony

There is no bundle: the registry is a service of the application's, every factory autowired with
the application's `http_client`.

```yaml
# config/services.yaml
services:
    Omnilang\Deepl\DeeplFactory: ~
    Omnilang\Libretranslate\LibretranslateFactory: ~

    Omnilang\Registry:
        arguments:
            $factories:
                - '@Omnilang\Deepl\DeeplFactory'
                - '@Omnilang\Libretranslate\LibretranslateFactory'
            $config:
                deepl:
                    factory: deepl
                    options:
                        key: '%env(DEEPL_API_KEY)%'
                    usage: { commercial: true }
                local:
                    factory: libretranslate
                    options:
                        base_uri: 'http://libretranslate:5000'
            $fallback: [deepl, local]
```

A key goes by reference: `'%env(DEEPL_API_KEY)%'`, its value in `.env.local` or
`bin/console secrets:set DEEPL_API_KEY` - or `'env:DEEPL_API_KEY'`, which omnilang reads itself.

A controller or a service type-hints `Omnilang\Registry`:

```php
public function __construct(private readonly Registry $lang) {}

$this->lang->translate(TranslationQuery::html($listing->getDescription(), 'ja', 'fr'));
```

A long document is a job for Messenger: `translateDocument()` in a handler, its `DocumentJob`
(without its key in `toArray()`) kept with the record, `documents($job->provider)->documentStatus()`
until done.

Google's access token lasts about an hour: build the registry per request (a factory service) with
the token the application's Google client gives, or rebuild it when the token is renewed.
