<?php

namespace Omnilang;

use Omnilang\Exception\FallbackFailedException;
use Omnilang\Exception\InvalidArgumentException;
use Omnilang\Exception\InvalidConfigException;
use Omnilang\Exception\NotSupportedException;
use Omnilang\Exception\ProviderException;
use Omnilang\Exception\UnreachableException;
use Omnilang\Exception\UsageLimitReachedException;
use Omnilang\Model\Detection;
use Omnilang\Model\DocumentJob;
use Omnilang\Model\DocumentQuery;
use Omnilang\Model\Translation;
use Omnilang\Model\TranslationQuery;
use Omnilang\Policy\GuardedDetector;
use Omnilang\Policy\GuardedDocuments;
use Omnilang\Policy\GuardedGlossary;
use Omnilang\Policy\GuardedTranslator;
use Omnisense\Contracts\Policy\RateLimiterInterface;
use Omnisense\Contracts\Provider\ProviderInterface as BaseProvider;
use Omnisense\Contracts\Provider\Registry as BaseRegistry;

/**
 * The application's engines by name, each built once from its factory and
 * options, each call through a contract guarded by the engine's usage
 * policy, and a fallback order across them:
 *
 *   $lang = new Registry([new DeeplFactory($http), new LibretranslateFactory($http)], [
 *       'deepl' => ['factory' => 'deepl', 'options' => ['key' => 'env:DEEPL_API_KEY']],
 *       'local' => ['factory' => 'libretranslate', 'options' => ['base_uri' => 'http://libretranslate:5000']],
 *   ], $limiter, fallback: ['deepl', 'local']);
 *
 *   $lang->translate(TranslationQuery::text('Bonjour', 'en'));        // DeepL, else the local server
 *   $lang->translator('local')->translate(TranslationQuery::html('<p>Merci</p>', 'ja'));
 *   $lang->detect(['Guten Morgen'])[0]->language;                    // the first engine that detects
 *
 * translator(), detector(), glossary() and documents() give one engine
 * behind its guard (Policy\Guarded*): each call checked against the
 * policy's rate limits for its operation - the contract's method name,
 * "translate", "languages", "detect", "createGlossary", "glossaries",
 * "glossary", "glossaryEntries", "deleteGlossary", "translateDocument",
 * "documentStatus", "documentResult" - then counted, refused with
 * UsageLimitReachedException once a limit is reached or while the engine
 * asks to wait. get() gives the engine itself, unguarded.
 *
 * translate(), detect() and translateDocument() go down the fallback
 * order: the one given to the call, else the registry's ($fallback), else
 * every engine keeping the contract in the configuration's order. An
 * engine that cannot take the query (HTML, a glossary of its own, a
 * document type it does not read) is passed over; one that fails - out of
 * reach, over a limit, an error answered - hands over to the next. When
 * none is left, FallbackFailedException holds every failure. A wrong query
 * (InvalidArgumentException) or a wrong configuration
 * (InvalidConfigException) is never passed on: it is thrown at once.
 */
final class Registry extends BaseRegistry
{
    /** @var string */
    public const PREFIX = Config::PREFIX;

    /** @var array<string, TranslatorInterface|DetectorInterface|GlossaryInterface|DocumentsInterface> the guarded contracts, by "contract name" */
    private array $guarded = [];

    /**
     * @param iterable<ProviderFactoryInterface>                                                                                         $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>, policy?: array<string, mixed>, usage?: array<string, mixed>}> $config
     * @param ?RateLimiterInterface                                                                                                      $limiter   where every engine's calls are counted: an in-memory one by default, a shared one for an application served by many processes
     * @param list<string>                                                                                                               $fallback  the engines to try in turn, by name; every configured one, in its order, when empty
     */
    public function __construct(iterable $factories, array $config, ?RateLimiterInterface $limiter = null, private readonly array $fallback = [])
    {
        parent::__construct($factories, $config, $limiter);
        foreach ($fallback as $name) {
            if (!isset($config[$name])) {
                throw new InvalidConfigException(\sprintf('The fallback order names "%s", which is not configured; configured: %s.', $name, implode(', ', array_keys($config)) ?: 'none'));
            }
        }
    }

    public function get(string $name): ProviderInterface
    {
        $provider = parent::get($name);

        return $provider instanceof ProviderInterface ? $provider : throw new InvalidConfigException(\sprintf('The "%s" provider (%s) is no omnilang provider.', $name, $provider::class));
    }

    public function translator(string $name): TranslatorInterface
    {
        $guarded = $this->guarded['translate '.$name] ??= new GuardedTranslator($this->contract($name, TranslatorInterface::class, 'translate'), $this->guard($name));

        return $guarded instanceof TranslatorInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function detector(string $name): DetectorInterface
    {
        $guarded = $this->guarded['detect '.$name] ??= new GuardedDetector($this->contract($name, DetectorInterface::class, 'detect languages'), $this->guard($name));

        return $guarded instanceof DetectorInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function glossary(string $name): GlossaryInterface
    {
        $guarded = $this->guarded['glossary '.$name] ??= new GuardedGlossary($this->contract($name, GlossaryInterface::class, 'keep glossaries'), $this->guard($name));

        return $guarded instanceof GlossaryInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function documents(string $name): DocumentsInterface
    {
        $guarded = $this->guarded['documents '.$name] ??= new GuardedDocuments($this->contract($name, DocumentsInterface::class, 'translate documents'), $this->guard($name));

        return $guarded instanceof DocumentsInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    /**
     * The configured engines keeping a contract ("translate", "detect",
     * "glossary", "documents"), in the configuration's order; every one
     * when null.
     *
     * @return list<string>
     */
    public function names(?string $contract = null): array
    {
        return parent::names($contract);
    }

    /**
     * The engines a contract's fallback goes down: the order given, else the
     * registry's, else every engine keeping it, in the configuration's
     * order - those that do not keep it left out.
     *
     * @param ?list<string> $order
     *
     * @return list<string>
     */
    public function fallbackOrder(string $contract = 'translate', ?array $order = null): array
    {
        $order ??= [] !== $this->fallback ? $this->fallback : $this->names();
        $keeping = $this->names($contract);

        return array_values(array_filter($order, static fn (string $name) => \in_array($name, $keeping, true)));
    }

    /**
     * The query translated by the first engine of the fallback order that
     * can: each Translation says which one ($provider).
     *
     * @param ?list<string> $order the engines to try, by name, for this call
     *
     * @return list<Translation>
     *
     * @throws FallbackFailedException when no engine could
     */
    public function translate(TranslationQuery $query, ?array $order = null): array
    {
        $order = $this->fallbackOrder('translate', $order);
        if (isset($query->glossaries['*']) && \count($order) > 1) {
            throw new InvalidArgumentException('A glossary belongs to one engine: name it by the engine\'s configured name (glossaries: ["deepl" => "…"]) when the registry falls back.');
        }

        return $this->fallBack('translate', $order, function (string $name) use ($query): array {
            $capabilities = $this->get($name)->capabilities();
            if ($query->isHtml() && !$capabilities->html) {
                throw NotSupportedException::call($name, 'translate HTML with its tags kept');
            }
            if (null !== $query->glossaryFor($name) && !$capabilities->glossaryInTranslation) {
                throw NotSupportedException::call($name, 'apply a glossary to a translation');
            }

            return $this->translator($name)->translate($query);
        });
    }

    /**
     * One text translated by the first engine that can.
     *
     * @param ?list<string> $order
     */
    public function translateText(string $text, string $to, ?string $from = null, ?array $order = null): Translation
    {
        return $this->translate(TranslationQuery::text($text, $to, $from), $order)[0];
    }

    /**
     * The texts' languages, said by the first engine of the fallback order
     * that detects.
     *
     * @param string|list<string> $texts
     * @param ?list<string>       $order
     *
     * @return list<Detection>
     */
    public function detect(string|array $texts, ?array $order = null): array
    {
        $texts = \is_string($texts) ? [$texts] : $texts;
        if ([] === $texts) {
            throw new InvalidArgumentException('A detection needs a text.');
        }

        return $this->fallBack('detect languages', $this->fallbackOrder('detect', $order), fn (string $name): array => $this->detector($name)->detect($texts));
    }

    /**
     * A document given to the first engine of the fallback order that reads
     * its type. Its job says which ($provider): follow it with
     * documents($job->provider).
     *
     * @param ?list<string> $order
     */
    public function translateDocument(DocumentQuery $query, ?array $order = null): DocumentJob
    {
        return $this->fallBack('translate the document', $this->fallbackOrder('documents', $order), function (string $name) use ($query): DocumentJob {
            if (!$this->get($name)->capabilities()->takesDocument($query->document->extension())) {
                throw NotSupportedException::call($name, \sprintf('translate a ".%s" document', $query->document->extension()));
            }

            return $this->documents($name)->translateDocument($query);
        });
    }

    protected function invalid(string $message): \Throwable
    {
        return new InvalidConfigException($message);
    }

    protected function notSupported(BaseProvider $provider, string $name, string $capability): \Throwable
    {
        return NotSupportedException::capability($provider, $name, $capability);
    }

    /**
     * @template T
     *
     * @param list<string>             $order
     * @param callable(string): T $call
     *
     * @return T
     */
    private function fallBack(string $what, array $order, callable $call): mixed
    {
        $failures = [];
        foreach ($order as $name) {
            try {
                return $call($name);
            } catch (NotSupportedException|UnreachableException|UsageLimitReachedException|ProviderException $e) {
                $failures[$name] = $e;
            }
        }

        throw new FallbackFailedException($what, $failures);
    }
}
