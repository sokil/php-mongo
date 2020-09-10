<?php

declare(strict_types=1);

namespace Sokil\Mongo\Event\Factory;

use Psr\EventDispatcher\StoppableEventInterface;
use Sokil\Mongo\Event\ConstructAfterEvent;
use Sokil\Mongo\Event\DeleteAfterEvent;
use Sokil\Mongo\Event\DeleteBeforeEvent;
use Sokil\Mongo\Event\InsertAfterEvent;
use Sokil\Mongo\Event\InsertBeforeEvent;
use Sokil\Mongo\Event\SaveAfterEvent;
use Sokil\Mongo\Event\SaveBeforeEvent;
use Sokil\Mongo\Event\UpdateAfterEvent;
use Sokil\Mongo\Event\UpdateBeforeEvent;
use Sokil\Mongo\Event\ValidateAfterEvent;
use Sokil\Mongo\Event\ValidateBeforeEvent;
use Sokil\Mongo\Event\ValidateErrorEvent;

/**
 * Default implementation of events.
 * For using custom events make your own implementation.
 */
class EventFactory implements EventFactoryInterface
{
    public function createConstructAfterEvent() : StoppableEventInterface
    {
        return new ConstructAfterEvent();
    }

    public function createDeleteAfterEvent() : StoppableEventInterface
    {
        return new DeleteAfterEvent();
    }

    public function createDeleteBeforeEvent() : StoppableEventInterface
    {
        return new DeleteBeforeEvent();
    }

    public function createInsertAfterEvent() : StoppableEventInterface
    {
        return new InsertAfterEvent();
    }

    public function createInsertBeforeEvent() : StoppableEventInterface
    {
        return new InsertBeforeEvent();
    }

    public function createSaveAfterEvent() : StoppableEventInterface
    {
        return new SaveAfterEvent();
    }

    public function createSaveBeforeEvent() : StoppableEventInterface
    {
        return new SaveBeforeEvent();
    }

    public function createUpdateAfterEvent() : StoppableEventInterface
    {
        return new UpdateAfterEvent();
    }

    public function createUpdateBeforeEvent() : StoppableEventInterface
    {
        return new UpdateBeforeEvent();
    }

    public function createValidateAfterEvent() : StoppableEventInterface
    {
        return new ValidateAfterEvent();
    }

    public function createValidateBeforeEvent() : StoppableEventInterface
    {
        return new ValidateBeforeEvent();
    }

    public function createValidateErrorEvent() : StoppableEventInterface
    {
        return new ValidateErrorEvent();
    }
}