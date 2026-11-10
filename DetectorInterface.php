<?php

namespace Omnilang;

use Omnilang\Model\Detection;

/** The language a text is in. */
interface DetectorInterface extends ProviderInterface
{
    /**
     * One Detection per text, in their order.
     *
     * @param list<string> $texts
     *
     * @return list<Detection>
     */
    public function detect(array $texts): array;
}
