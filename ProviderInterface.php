<?php

namespace Omnilang;

/**
 * One engine, configured: DeepL, Google Cloud Translation, Azure AI
 * Translator, Amazon Translate, a LibreTranslate server... Its Capabilities
 * say what it can be asked, by contract - translate a text or a page
 * (TranslatorInterface), say what language a text is in (DetectorInterface),
 * keep glossaries (GlossaryInterface), translate whole documents
 * (DocumentsInterface) - and its usage policy.
 */
interface ProviderInterface extends \Omnisense\Contracts\Provider\ProviderInterface
{
    /** The factory's name: "deepl", "google", "azure", "amazon", "libretranslate"... */
    public function getName(): string;

    public function getTitle(): string;

    public function capabilities(): Capabilities;
}
