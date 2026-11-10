<?php

namespace Omnilang;

/** Builds a provider from its options (a key by reference, a region, a server's address...). */
interface ProviderFactoryInterface extends \Omnisense\Contracts\Provider\ProviderFactoryInterface
{
    /** The name providers are configured with: "deepl", "google", "azure", "amazon", "libretranslate"... */
    public function getName(): string;

    /** @param array<string, mixed> $options */
    public function create(array $options = []): ProviderInterface;
}
