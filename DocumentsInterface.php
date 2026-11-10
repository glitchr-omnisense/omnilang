<?php

namespace Omnilang;

use Omnilang\Model\Document;
use Omnilang\Model\DocumentJob;
use Omnilang\Model\DocumentQuery;

/**
 * Whole documents translated, their layout kept. A synchronous engine's
 * job is done at once (its result inside); an asynchronous one's is
 * followed with documentStatus() until done, then documentResult().
 */
interface DocumentsInterface extends ProviderInterface
{
    public function translateDocument(DocumentQuery $query): DocumentJob;

    /** The job as the engine sees it now. */
    public function documentStatus(DocumentJob $job): DocumentJob;

    /** The translated document of a job that is done (DeepL gives it once, then forgets it). */
    public function documentResult(DocumentJob $job): Document;
}
