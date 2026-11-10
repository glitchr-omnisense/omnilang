<?php

namespace Omnilang\Model;

/**
 * The register asked of a translation, where the engine and the target
 * language have one ("vous" or "tu", "Sie" or "du"): a preference, never a
 * reason to fail - DeepL's prefer_more / prefer_less, Amazon's FORMAL /
 * INFORMAL. An engine without formality (Capabilities::$formality false)
 * translates in its default register.
 */
enum Formality: string
{
    case Formal = 'formal';
    case Informal = 'informal';
}
