<?php

/*
 * The omnilang/* providers the harness installs beside the core: their
 * tests' namespace, and what their tests need besides (require-dev).
 */
return [
    'deepl' => ['Omnilang\\Deepl\\Tests\\'],
    'google' => ['Omnilang\\Google\\Tests\\'],
    'azure' => ['Omnilang\\Azure\\Tests\\'],
    'amazon' => ['Omnilang\\Amazon\\Tests\\'],
    'libretranslate' => ['Omnilang\\Libretranslate\\Tests\\'],
];
