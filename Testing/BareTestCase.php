<?php

namespace Omnilang\Testing;

use Omnilang\Registry;

/**
 * The check every package of the family runs: each of its classes loaded in
 * a PHP process of its own (this one has loaded PHPUnit), with nothing from
 * outside but omnisense/omnilang, omnisense/contracts and what the package
 * requires - no framework, no HTTP client, no application, even in the
 * harness where they are installed beside it. omnisense/contracts'
 * BareTestCase, with omnilang's core among the folders that may load and
 * its harness's OMNILANG_AUTOLOAD.
 *
 * @suggested phpunit/phpunit (test support: loaded by a package's tests only)
 */
abstract class BareTestCase extends \Omnisense\Contracts\Testing\BareTestCase
{
    /** @var non-empty-string */
    protected const AUTOLOAD_ENV = 'OMNILANG_AUTOLOAD';

    protected static function coreDirs(): array
    {
        return [...parent::coreDirs(), self::dirOf(Registry::class)];
    }
}
