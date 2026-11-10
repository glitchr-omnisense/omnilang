<?php

namespace Omnilang\Policy;

use Omnilang\GlossaryInterface;
use Omnilang\Model\Glossary;
use Omnilang\Model\Language;
use Omnisense\Contracts\Policy\PolicyGuard;

/**
 * A provider's glossaries behind its guard: each call is the operation of
 * its method's name ("createGlossary", "glossaries", "glossary",
 * "glossaryEntries", "deleteGlossary").
 *
 * @extends Guarded<GlossaryInterface>
 */
final class GuardedGlossary extends Guarded implements GlossaryInterface
{
    public function __construct(GlossaryInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function createGlossary(string $name, Language $source, Language $target, array $entries): Glossary
    {
        return $this->guard->call('createGlossary', fn () => $this->provider->createGlossary($name, $source, $target, $entries));
    }

    public function glossaries(): array
    {
        return $this->guard->call('glossaries', fn () => $this->provider->glossaries());
    }

    public function glossary(string $id): ?Glossary
    {
        return $this->guard->call('glossary', fn () => $this->provider->glossary($id));
    }

    public function glossaryEntries(string $id, Language $source, Language $target): array
    {
        return $this->guard->call('glossaryEntries', fn () => $this->provider->glossaryEntries($id, $source, $target));
    }

    public function deleteGlossary(string $id): void
    {
        $this->guard->call('deleteGlossary', fn () => $this->provider->deleteGlossary($id));
    }
}
