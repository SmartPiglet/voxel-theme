<?php

declare (strict_types=1);
namespace Voxel\Vendor\Paddle\SDK\Notifications\Events;

use Voxel\Vendor\Paddle\SDK\Entities\Event;
use Voxel\Vendor\Paddle\SDK\Entities\Event\EventTypeName;
use Voxel\Vendor\Paddle\SDK\Notifications\Entities\ClientToken;
use Voxel\Vendor\Paddle\SDK\Notifications\Entities\Entity;
final class ClientTokenRevoked extends Event
{
    private function __construct(string $eventId, EventTypeName $eventType, \DateTimeInterface $occurredAt, public readonly ClientToken $clientToken, string|null $notificationId)
    {
        parent::__construct($eventId, $eventType, $occurredAt, $clientToken, $notificationId);
    }
    /**
     * @param ClientToken $data
     */
    public static function fromEvent(string $eventId, EventTypeName $eventType, \DateTimeInterface $occurredAt, Entity $data, string|null $notificationId = null): static
    {
        return new self($eventId, $eventType, $occurredAt, $data, $notificationId);
    }
}
