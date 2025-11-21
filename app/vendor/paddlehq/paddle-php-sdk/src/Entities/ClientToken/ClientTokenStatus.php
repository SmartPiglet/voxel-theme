<?php

declare (strict_types=1);
namespace Voxel\Vendor\Paddle\SDK\Entities\ClientToken;

use Voxel\Vendor\Paddle\SDK\PaddleEnum;
/**
 * @method static ClientTokenStatus Active()
 * @method static ClientTokenStatus Revoked()
 */
final class ClientTokenStatus extends PaddleEnum
{
    private const Active = 'active';
    private const Revoked = 'revoked';
}
