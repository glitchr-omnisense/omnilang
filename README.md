# omnisense/omnilang

Translate a text or a page, whatever the engine, under any PHP host: one family for machine
translation, in four contracts, with a fallback from one engine to the next.

| Contract | What it answers |
|---|---|
| `TranslatorInterface` | texts translated, plain or HTML with its tags kept, a register (formal, informal) where the engine has one, a glossary where it keeps them; the source language detected when none is given; the languages it translates |
| `DetectorInterface` | the language a text is in, how sure the engine is, the other languages it thought of |
| `GlossaryInterface` | the engine's glossaries made, listed, read, deleted - and applied through a translation |
| `DocumentsInterface` | whole documents translated, their layout kept: at once, or as a job followed until done |

| Provider | Contracts | Credentials |
|---|---|---|
| [omnilang/deepl](https://github.com/glitchr-omnisense/omnilang-deepl) | translate (HTML, formality), glossary (v3), documents (async) | a key |
| [omnilang/google](https://github.com/glitchr-omnisense/omnilang-google) | translate (HTML), detect, glossary (no create), documents (PDF, Office) - Cloud Translation v3 | an OAuth access token |
| [omnilang/azure](https://github.com/glitchr-omnisense/omnilang-azure) | translate (HTML), detect, documents (sync, custom domain) - Translator v3.0 | a key and its region |
| [omnilang/amazon](https://github.com/glitchr-omnisense/omnilang-amazon) | translate (formality), glossary (terminologies), documents (HTML, text, DOCX) | IAM credentials (Signature Version 4, no SDK) |
| [omnilang/libretranslate](https://github.com/glitchr-omnisense/omnilang-libretranslate) | translate (HTML), detect, documents - a self-hosted server | none by default |

## Installation and a first call

```sh
composer require omnisense/omnilang omnilang/deepl
```

Every engine needs an account and its credentials (the table above), except LibreTranslate, which needs a server
of one's own. The first call below needs neither: the recorded client of the Testing kit (omnisense/contracts')
answers for DeepL, as the tests do:

```php
require __DIR__.'/vendor/autoload.php';

use Omnilang\Deepl\DeeplFactory;
use Omnilang\Registry;
use Omnilang\Testing\RecordedHttpClient;

$http = RecordedHttpClient::routes([            // DeepL's answer, recorded: the application's HTTP client in production
    '~^POST https://api-free\.deepl\.com/v2/translate$~' => [200, '{"translations": [{"detected_source_language": "FR", "text": "Double room with a view"}]}'],
]);
$lang = new Registry([new DeeplFactory($http)], ['deepl' => ['factory' => 'deepl', 'options' => ['key' => 'a-free-key:fx']]]);

echo $lang->translateText('Chambre double avec vue', 'en')->text, "\n";   // Double room with a view
```

Several engines, one after the other:

```php
use Omnilang\Model\{Formality, TranslationQuery};
use Omnilang\Registry;

$lang = new Registry([new DeeplFactory($http), new LibretranslateFactory($http)], [
    'deepl' => ['factory' => 'deepl', 'options' => ['key' => 'env:DEEPL_API_KEY']],
    'local' => ['factory' => 'libretranslate', 'options' => ['base_uri' => 'http://libretranslate:5000']],
], fallback: ['deepl', 'local']);

$lang->translateText('Chambre double avec vue', 'en')->text;          // DeepL, else the local server
$lang->translate(TranslationQuery::html('<p>Réservez <b>maintenant</b></p>', 'ja', formality: Formality::Formal));
$lang->detect('Guten Morgen')[0]->language->code;                       // "de", by the first engine that detects
```

Keys go by reference: `'env:DEEPL_API_KEY'` is read from the environment when the engine is built
(or the application passes the value it reads from its vault, `%env(DEEPL_API_KEY)%` in Symfony);
a configuration file never holds a key, and no error, copy or trace ever quotes one.

## Fallback

`translate()`, `detect()` and `translateDocument()` go down an order: the one given to the call,
else the registry's `fallback`, else every engine keeping the contract in the configuration's
order. An engine that cannot take the query (HTML, a glossary it does not keep, a document type it
does not read) is passed over; one that fails - out of reach, over a limit, an error answered (a
spent quota: DeepL's 456, Google's 403) - hands over to the next. When none is left,
`FallbackFailedException` holds every failure by name. A wrong query or configuration is thrown at
once, never passed on. Each `Translation` says which engine made it (`$provider`).

`translator()`, `detector()`, `glossary()` and `documents()` give one engine, behind its guard;
`get()` the engine itself.

## Usage policies

Each provider declares its usage policy as data (its `policy.json`, named in its composer.json's
`extra.omnisense.policy`): rate limits, rights (commercial use, caching, storage, attribution),
requirements, and where and when it was read - with what a call count cannot say (quotas in
characters, data retention) in its notes. Every call through the registry is held to it: checked
before, counted after, refused with `UsageLimitReachedException` once a limit is reached or while
the engine asks to wait (a 429 and its `Retry-After`). A query longer than one request of the engine
takes is cut into as many requests, each counted. A site may tighten a policy, never loosen it:

```php
'deepl' => [
    'factory' => 'deepl',
    'options' => ['key' => 'env:DEEPL_API_KEY'],
    'policy' => ['rateLimits' => [['max' => 2000, 'window' => 'day', 'operations' => ['translate']]]],
    'usage' => ['commercial' => true],
],
```

`$lang->policy('deepl')`, `$lang->policies()`: what a site's "about our translations" page shows
(DeepL and Google require their attribution beside every translation). See
[docs/policies.md](docs/policies.md).

The boundaries: a site's own catalogues (messages.fr.yaml, Crowdin, Lokalise) are Symfony's
translation providers', not this family's; a translation by a language model goes through Symfony
AI, not omnilang.

`omnisense/omnilang` requires `omnisense/contracts` (the kernel every omnisense family builds on:
provider, factory, registry, JSON client, usage policies, testing kit) and
`symfony/http-client-contracts`, nothing else: no framework, no HTTP client of its own (each
provider requires `symfony/http-client` and takes the application's).

```sh
composer require omnisense/omnilang omnilang/deepl
```

## Documentation

[docs/index.md](docs/index.md): installation (plain PHP first), Symfony, models, providers,
policies, testing, the harness, errors, writing a provider.

## Licence

MIT
