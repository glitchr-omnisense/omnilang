---
title: Installation
order: 10
---

# Installation

```sh
composer require omnisense/omnilang omnilang/deepl      # one package per engine
```

PHP 8.2 or later with `mbstring`. The core requires `omnisense/contracts` and
`symfony/http-client-contracts` (interfaces only); a provider requires `symfony/http-client`, a
library that stands alone, and takes the application's client when given one.

## Plain PHP

No engine, no network: the core's `FixedProvider` answers from what it was given, as an engine
would have returned it - and passes a text it does not hold to the next engine.

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnilang\Model\{Document, DocumentQuery, Language, TranslationQuery};
use Omnilang\Registry;
use Omnilang\Testing\FixedProviderFactory;

$lang = new Registry([
    new FixedProviderFactory(['en' => ['Bonjour' => 'Hello']], [], 'plain'),
    new FixedProviderFactory(['en' => ['<p>Merci</p>' => '<p>Thank you</p>']], ['Guten Morgen' => 'de'], 'rich'),
], [
    'plain' => ['factory' => 'plain', 'options' => ['html' => false]],
    'rich' => ['factory' => 'rich'],
], fallback: ['plain', 'rich']);

echo $lang->translateText('Bonjour', 'en')->text, "\n";                          // Hello (plain)
echo $lang->translate(TranslationQuery::html('<p>Merci</p>', 'en'))[0]->provider, "\n"; // rich: plain keeps no tags
echo $lang->detect('Guten Morgen')[0]->language, "\n";                          // de
```

With a real engine, only the factory and its options change:

```php
use Omnilang\Deepl\DeeplFactory;
use Symfony\Component\HttpClient\HttpClient;

$lang = new Registry([new DeeplFactory(HttpClient::create())], [
    'deepl' => ['factory' => 'deepl', 'options' => ['key' => 'env:DEEPL_API_KEY']],
]);
```

`env:DEEPL_API_KEY` is read from the environment (`$_ENV`, `$_SERVER`, `getenv()`) when the
engine is built; a missing variable is said by its name, never by a value.

The registry is built once per process. An application served by many processes passes a shared
`RateLimiterInterface` as its third argument (omnibridge/symfony has one over
`symfony/rate-limiter`), so that limits count across them.

## Frameworks

- [Symfony](symfony.md).
- Laravel: the registry is a singleton of a service provider, built the same way; `env()` or
  `config()` gives the keys.
