<?php

declare (strict_types=1);
namespace Voxel\Vendor\OTPHP;

use DateTimeImmutable;
use Voxel\Vendor\Psr\Clock\ClockInterface;
/**
 * @internal
 */
final class InternalClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
