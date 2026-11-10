<?php

namespace Omnilang;

use Omnisense\Contracts\Policy\UsagePolicy;
use Omnisense\Contracts\Provider\CapabilitiesInterface;

/**
 * What a provider is, said before it is asked anything: the contracts it
 * keeps, what each one can carry (HTML with its tags kept, formality, a
 * glossary in a translation, the document types it takes), its limits per
 * request, and its usage policy (omnisense/contracts' UsagePolicy: rate
 * limits, rights, attribution, data retention in its notes).
 */
final readonly class Capabilities implements CapabilitiesInterface
{
    /**
     * @param bool         $translate              texts translated (TranslatorInterface)
     * @param bool         $detect                 a text's language said (DetectorInterface)
     * @param bool         $glossary               glossaries kept, listed, read, deleted (GlossaryInterface)
     * @param bool         $documents              whole documents translated (DocumentsInterface)
     * @param bool         $html                   HTML translated with its tags kept (TextFormat::Html)
     * @param bool         $formality              a formal or informal register asked (Formality)
     * @param bool         $glossaryInTranslation  a glossary applied to a translation (TranslationQuery's glossaries)
     * @param bool         $glossaryCreate         GlossaryInterface::createGlossary() (Google builds glossaries from Cloud Storage files only)
     * @param bool         $asyncDocuments         a document job to follow (documentStatus()), not a result at once
     * @param bool         $detectsSource          a translation without a source language says the one it detected
     * @param list<string> $documentTypes          the document types it takes, as file extensions ("docx", "pdf", "html")
     * @param ?int         $maxTexts               the texts one request may hold (the registry splits beyond)
     * @param ?int         $maxCharacters          the characters one request may hold, all texts together
     * @param bool         $selfHosted             a server the site runs itself
     */
    public function __construct(
        private UsagePolicy $policy,
        public bool $translate = false,
        public bool $detect = false,
        public bool $glossary = false,
        public bool $documents = false,
        public bool $html = false,
        public bool $formality = false,
        public bool $glossaryInTranslation = false,
        public bool $glossaryCreate = false,
        public bool $asyncDocuments = false,
        public bool $detectsSource = false,
        public array $documentTypes = [],
        public ?int $maxTexts = null,
        public ?int $maxCharacters = null,
        public bool $selfHosted = false,
    ) {
    }

    public function policy(): UsagePolicy
    {
        return $this->policy;
    }

    /** @return list<string> the contracts it keeps: translate, detect, glossary, documents */
    public function contracts(): array
    {
        return array_keys(array_filter(['translate' => $this->translate, 'detect' => $this->detect, 'glossary' => $this->glossary, 'documents' => $this->documents]));
    }

    /** Whether it takes a document of this extension ("docx", ".PDF", "report.html"). */
    public function takesDocument(string $extension): bool
    {
        $extension = strtolower(ltrim((string) pathinfo('x.'.ltrim($extension, '.'), \PATHINFO_EXTENSION), '.'));

        return \in_array($extension, $this->documentTypes, true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contracts' => $this->contracts(),
            'html' => $this->html, 'formality' => $this->formality, 'glossaryInTranslation' => $this->glossaryInTranslation, 'glossaryCreate' => $this->glossaryCreate,
            'asyncDocuments' => $this->asyncDocuments, 'detectsSource' => $this->detectsSource, 'documentTypes' => $this->documentTypes,
            'maxTexts' => $this->maxTexts, 'maxCharacters' => $this->maxCharacters, 'selfHosted' => $this->selfHosted,
            'policy' => $this->policy->toArray(),
        ];
    }
}
