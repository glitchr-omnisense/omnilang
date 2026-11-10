<?php

namespace Omnilang;

use Omnilang\Model\SupportedLanguage;
use Omnilang\Model\Translation;
use Omnilang\Model\TranslationQuery;

/** Texts translated: plain or HTML, a register where the engine has one, a glossary where it keeps them. */
interface TranslatorInterface extends ProviderInterface
{
    /**
     * One Translation per text of the query, in its order.
     *
     * @return list<Translation>
     */
    public function translate(TranslationQuery $query): array;

    /**
     * The languages it translates from and into.
     *
     * @return list<SupportedLanguage>
     */
    public function languages(): array;
}
