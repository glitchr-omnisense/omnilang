<?php

namespace Omnilang;

use Omnilang\Model\Glossary;
use Omnilang\Model\Language;

/** The glossaries an engine keeps: made, listed, read, deleted; applied through TranslationQuery's glossaries. */
interface GlossaryInterface extends ProviderInterface
{
    /**
     * A glossary of one direction, from its entries (source term => target
     * term). Capabilities::$glossaryCreate false: NotSupportedException.
     *
     * @param array<string, string> $entries
     */
    public function createGlossary(string $name, Language $source, Language $target, array $entries): Glossary;

    /** @return list<Glossary> */
    public function glossaries(): array;

    /** A glossary by its id; null when the engine knows none. */
    public function glossary(string $id): ?Glossary;

    /**
     * A glossary's entries of one direction (source term => target term).
     *
     * @return array<string, string>
     */
    public function glossaryEntries(string $id, Language $source, Language $target): array;

    public function deleteGlossary(string $id): void;
}
