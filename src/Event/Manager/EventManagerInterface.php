<?php

declare(strict_types=1);

namespace Sokil\Mongo\Event\Manager;

use Psr\EventDispatcher\EventDispatcherInterface;

interface EventManagerInterface extends EventDispatcherInterface
{
    public function hasListeners(string $eventName = null): bool;

    public function addListener(string $eventName, $listener, int $priority = 0): void;

    public function removeListener(string $eventName, $listener): void;
}