<?php

namespace Omnilang\Model;

/**
 * A glossary an engine keeps: the terms a site wants translated its own
 * way ("Chambre d'hôtes" → "Bed and breakfast", a brand left as it is).
 * Its id is the engine's (DeepL's glossary_id, Google's glossary name,
 * Amazon's terminology name), what TranslationQuery's glossaries name;
 * its pairs the directions it holds (one for most engines; a DeepL
 * glossary may hold several dictionaries, an Amazon terminology one source
 * and several targets).
 */
final readonly class Glossary
{
    /** @param list<LanguagePair> $pairs */
    public function __construct(
        public string $id,
        public string $name,
        public string $provider,
        public array $pairs = [],
        public ?\DateTimeImmutable $createdAt = null,
    ) {
    }

    /** The entries of every pair, when the engine counts them. */
    public function entries(): ?int
    {
        $counts = array_map(static fn (LanguagePair $pair) => $pair->entries, $this->pairs);

        return [] === $counts || \in_array(null, $counts, true) ? null : array_sum($counts);
    }

    public function covers(string|Language $source, string|Language $target): bool
    {
        foreach ($this->pairs as $pair) {
            if ($pair->matches($source, $target)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Entries as tab-separated values, the form DeepL and Amazon import:
     * one "source<TAB>target" a line; tabs and line breaks inside a term
     * become spaces.
     *
     * @param array<string, string> $entries
     */
    public static function tsv(array $entries): string
    {
        $clean = static fn (string $term) => trim((string) preg_replace('~[\t\r\n]+~', ' ', $term));
        $lines = [];
        foreach ($entries as $source => $target) {
            $lines[] = $clean((string) $source)."\t".$clean($target);
        }

        return implode("\n", $lines);
    }

    /**
     * Tab-separated entries read back; lines without a tab are skipped.
     *
     * @return array<string, string>
     */
    public static function fromTsv(string $tsv): array
    {
        $entries = [];
        foreach (preg_split('~\r\n|\n|\r~', $tsv) ?: [] as $line) {
            $cells = explode("\t", $line);
            if (\count($cells) >= 2 && '' !== trim($cells[0])) {
                $entries[trim($cells[0])] = trim($cells[1]);
            }
        }

        return $entries;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'provider' => $this->provider,
            'pairs' => array_map(static fn (LanguagePair $p) => ['source' => $p->source->code, 'target' => $p->target->code, 'entries' => $p->entries], $this->pairs),
            'createdAt' => $this->createdAt?->format(\DATE_ATOM),
        ];
    }
}
