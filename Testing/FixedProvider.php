<?php

namespace Omnilang\Testing;

use Omnilang\Capabilities;
use Omnilang\DetectorInterface;
use Omnilang\DocumentsInterface;
use Omnilang\Exception\NotSupportedException;
use Omnilang\GlossaryInterface;
use Omnilang\Model\Detection;
use Omnilang\Model\Document;
use Omnilang\Model\DocumentJob;
use Omnilang\Model\DocumentQuery;
use Omnilang\Model\DocumentStatus;
use Omnilang\Model\Glossary;
use Omnilang\Model\Language;
use Omnilang\Model\LanguagePair;
use Omnilang\Model\SupportedLanguage;
use Omnilang\Model\Translation;
use Omnilang\Model\TranslationQuery;
use Omnilang\TranslatorInterface;
use Omnisense\Contracts\Policy\Permission;
use Omnisense\Contracts\Policy\UsagePolicy;

/**
 * An engine that answers from what it was given, with no network: an
 * application's tests, the harness's bare script. Translations from a
 * dictionary by target language (a text it does not hold is
 * NotSupportedException, so that a registry falls back past it), its
 * glossaries in memory and applied first, detections by text (a text it
 * does not know is NotSupportedException too), documents translated line by
 * line, at once.
 */
final class FixedProvider implements TranslatorInterface, DetectorInterface, GlossaryInterface, DocumentsInterface
{
    /** @var array<string, array{glossary: Glossary, entries: array<string, string>}> */
    private array $glossaries = [];

    private int $jobs = 0;

    /**
     * @param array<string, array<string, string>> $translations translated text by text, by target language ("en" => ["Bonjour" => "Hello"])
     * @param array<string, string>                $detections   language by text
     */
    public function __construct(
        private readonly array $translations = [],
        private readonly array $detections = [],
        private readonly string $name = 'fixed',
        private readonly bool $html = true,
        private readonly ?int $maxTexts = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return 'Fixed translations';
    }

    public function capabilities(): Capabilities
    {
        $policy = UsagePolicy::builder($this->name)->noLimits()->commercial(Permission::Allowed)->provenance(notes: 'Answers from what it was given: no engine, no terms.')->build();

        return new Capabilities($policy, translate: true, detect: true, glossary: true, documents: true, html: $this->html, formality: false, glossaryInTranslation: true, glossaryCreate: true, detectsSource: true, documentTypes: ['txt', 'html'], maxTexts: $this->maxTexts);
    }

    public function translate(TranslationQuery $query): array
    {
        $glossary = $query->glossaryFor($this->name);
        $entries = null === $glossary ? [] : ($this->glossaries[$glossary]['entries'] ?? throw NotSupportedException::call($this->name, \sprintf('know the glossary "%s"', $glossary)));
        $dictionary = $this->dictionary($query->target);

        return array_map(function (string $text) use ($query, $entries, $dictionary, $glossary): Translation {
            $translated = $entries[$text] ?? $dictionary[$text] ?? throw NotSupportedException::call($this->name, \sprintf('hold a translation of "%s" into %s', mb_substr($text, 0, 40), $query->target));
            $detected = null === $query->source ? Language::tryOf($this->detections[$text] ?? null) : null;

            return new Translation($translated, $query->target, $this->name, $query->source, $detected, $query->format, mb_strlen($text), $glossary);
        }, $query->texts);
    }

    public function languages(): array
    {
        return array_map(static fn (string $target) => new SupportedLanguage(Language::of($target), null, true, true), array_keys($this->translations));
    }

    public function detect(array $texts): array
    {
        return array_map(fn (string $text) => new Detection(Language::of($this->detections[$text] ?? throw NotSupportedException::call($this->name, \sprintf('know the language of "%s"', mb_substr($text, 0, 40)))), $this->name, 1.0), $texts);
    }

    public function createGlossary(string $name, Language $source, Language $target, array $entries): Glossary
    {
        $id = 'fixed-'.(\count($this->glossaries) + 1);
        $glossary = new Glossary($id, $name, $this->name, [new LanguagePair($source, $target, \count($entries))], new \DateTimeImmutable('2026-11-10T00:00:00Z'));
        $this->glossaries[$id] = ['glossary' => $glossary, 'entries' => $entries];

        return $glossary;
    }

    public function glossaries(): array
    {
        return array_values(array_map(static fn (array $one) => $one['glossary'], $this->glossaries));
    }

    public function glossary(string $id): ?Glossary
    {
        return $this->glossaries[$id]['glossary'] ?? null;
    }

    public function glossaryEntries(string $id, Language $source, Language $target): array
    {
        $one = $this->glossaries[$id] ?? null;

        return null !== $one && $one['glossary']->covers($source, $target) ? $one['entries'] : [];
    }

    public function deleteGlossary(string $id): void
    {
        unset($this->glossaries[$id]);
    }

    public function translateDocument(DocumentQuery $query): DocumentJob
    {
        $dictionary = $this->dictionary($query->target);
        $lines = preg_split('~(\r\n|\n)~', $query->document->content, -1, \PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $content = implode('', array_map(static fn (string $line) => $dictionary[$line] ?? $line, $lines));
        $result = new Document($content, $query->translatedName(), $query->document->mimeType);

        return new DocumentJob('fixed-job-'.++$this->jobs, $this->name, DocumentStatus::Done, $query->target, $query->source, filename: $result->filename, result: $result);
    }

    public function documentStatus(DocumentJob $job): DocumentJob
    {
        return $job;
    }

    public function documentResult(DocumentJob $job): Document
    {
        return $job->result ?? throw NotSupportedException::call($this->name, 'know this document job');
    }

    /** @return array<string, string> */
    private function dictionary(Language $target): array
    {
        return $this->translations[$target->code] ?? $this->translations[$target->primary()] ?? [];
    }
}
