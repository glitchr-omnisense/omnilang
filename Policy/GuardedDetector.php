<?php

namespace Omnilang\Policy;

use Omnilang\DetectorInterface;
use Omnisense\Contracts\Policy\PolicyGuard;

/**
 * A provider's detections behind its guard: each call is the operation
 * "detect", its texts' characters reported to a limit counted in
 * "characters".
 *
 * @extends Guarded<DetectorInterface>
 */
final class GuardedDetector extends Guarded implements DetectorInterface
{
    public function __construct(DetectorInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function detect(array $texts): array
    {
        return $this->guard->call('detect', fn () => $this->provider->detect($texts), amounts: ['characters' => array_sum(array_map('mb_strlen', $texts))]);
    }
}
