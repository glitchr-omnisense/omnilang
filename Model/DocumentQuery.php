<?php

namespace Omnilang\Model;

/** A document to translate into one language, from one (null: detected, where the engine can), with a register and glossaries as a TranslationQuery has them. */
final readonly class DocumentQuery
{
    public Language $target;

    public ?Language $source;

    /** @var array<string, string> */
    public array $glossaries;

    /** @param string|array<mixed>|null $glossaries an id for whichever provider is called, or ids by provider name */
    public function __construct(
        public Document $document,
        string|Language $target,
        string|Language|null $source = null,
        public ?Formality $formality = null,
        string|array|null $glossaries = null,
    ) {
        $this->target = Language::of($target);
        $this->source = null === $source ? null : Language::of($source);
        $this->glossaries = TranslationQuery::glossaryIds($glossaries);
    }

    public function glossaryFor(string $provider): ?string
    {
        return $this->glossaries[$provider] ?? $this->glossaries['*'] ?? null;
    }

    /** The translated file's name: "report.docx" into Japanese is "report.ja.docx". */
    public function translatedName(?string $extension = null): string
    {
        $info = pathinfo($this->document->filename);

        return $info['filename'].'.'.$this->target->code.'.'.($extension ?? $info['extension'] ?? 'bin');
    }
}
