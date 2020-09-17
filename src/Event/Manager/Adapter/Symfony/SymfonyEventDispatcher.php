<?php

declare(strict_types=1);

namespace Sokil\Mongo\Event\Manager\Adapter\Symfony;

use Sokil\Mongo\Event\Manager\EventManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SymfonyEventDispatcher implements EventManagerInterface
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function hasListeners(string $eventName = null): bool
    {
        return $this->hasListeners($eventName);
    }

    public function addListener(string $eventName, $listener, int $priority = 0): void
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    public function removeListener(string $eventName, $listener): void
    {
        $this->dispatcher->removeListener($eventName, $listener);
    }

    /**
     * @param object $event
     *
     * @return object
     */
    public function dispatch(object $event)
    {
        return $this->dispatcher->dispatch($event);
    }
}