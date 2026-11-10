<?php

namespace Omnilang\Policy;

use Omnilang\DocumentsInterface;
use Omnilang\Model\Document;
use Omnilang\Model\DocumentJob;
use Omnilang\Model\DocumentQuery;
use Omnisense\Contracts\Policy\PolicyGuard;

/**
 * A provider's documents behind its guard: each call is the operation of
 * its method's name ("translateDocument", "documentStatus",
 * "documentResult").
 *
 * @extends Guarded<DocumentsInterface>
 */
final class GuardedDocuments extends Guarded implements DocumentsInterface
{
    public function __construct(DocumentsInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function translateDocument(DocumentQuery $query): DocumentJob
    {
        return $this->guard->call('translateDocument', fn () => $this->provider->translateDocument($query));
    }

    public function documentStatus(DocumentJob $job): DocumentJob
    {
        return $this->guard->call('documentStatus', fn () => $this->provider->documentStatus($job));
    }

    public function documentResult(DocumentJob $job): Document
    {
        return $this->guard->call('documentResult', fn () => $this->provider->documentResult($job));
    }
}
