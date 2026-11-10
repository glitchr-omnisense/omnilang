<?php

namespace Omnilang\Testing;

use Omnilang\Config;
use Omnilang\ProviderFactory;
use Omnilang\ProviderInterface;

/** FixedProvider's factory: "fixed" (or the name given), answering from what it holds. Options: html (false: an engine that translates plain text only), max_texts (the texts one request may hold). */
final class FixedProviderFactory extends ProviderFactory
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param array<string, string>                $detections
     */
    public function __construct(
        private readonly array $translations = [],
        private readonly array $detections = [],
        private readonly string $name = 'fixed',
    ) {
    }

    protected function populate(Config $c): void
    {
        $c->defaults(['omnilang.factory_name' => $this->name, 'omnilang.factory_title' => 'Fixed translations', 'omnilang.required_options' => [], 'html' => true, 'max_texts' => null]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new FixedProvider($this->translations, $this->detections, $this->name, $c->bool('html'), null === $c->string('max_texts') ? null : $c->int('max_texts'));
    }
}
