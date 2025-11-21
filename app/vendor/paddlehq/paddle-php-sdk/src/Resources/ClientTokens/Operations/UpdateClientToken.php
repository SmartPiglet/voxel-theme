<?php

declare (strict_types=1);
namespace Voxel\Vendor\Paddle\SDK\Resources\ClientTokens\Operations;

use Voxel\Vendor\Paddle\SDK\Entities\ClientToken\ClientTokenStatus;
use Voxel\Vendor\Paddle\SDK\FiltersUndefined;
use Voxel\Vendor\Paddle\SDK\Undefined;
class UpdateClientToken implements \JsonSerializable
{
    use FiltersUndefined;
    public function __construct(public readonly ClientTokenStatus|Undefined $status = new Undefined())
    {
    }
    public function jsonSerialize(): array
    {
        return $this->filterUndefined(['status' => $this->status]);
    }
}
