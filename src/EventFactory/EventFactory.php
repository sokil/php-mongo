<?php
declare(strict_types=1);

namespace Sokil\Mongo\EventFactory;

use Psr\EventDispatcher\StoppableEventInterface;
use Sokil\Mongo\Document;
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

    public function create($eventName)
    {
        switch($eventName)
        {
            case Document::EVENT_NAME_AFTER_CONSTRUCT:
                $event = $this->createConstructAfterEvent();
                break;
            case Document::EVENT_NAME_AFTER_DELETE:
                $event = $this->createDeleteAfterEvent();
                break;
            case Document::EVENT_NAME_BEFORE_DELETE:
                $event = $this->createDeleteBeforeEvent();
                break;
            case Document::EVENT_NAME_AFTER_INSERT:
                $event = $this->createInsertAfterEvent();
                break;
            case Document::EVENT_NAME_BEFORE_INSERT:
                $event = $this->createInsertBeforeEvent();
                break;
            case Document::EVENT_NAME_AFTER_SAVE:
                $event = $this->createSaveAfterEvent();
                break;
            case Document::EVENT_NAME_BEFORE_SAVE:
                $event = $this->createSaveBeforeEvent();
                break;
            case Document::EVENT_NAME_AFTER_UPDATE:
                $event = $this->createUpdateAfterEvent();
                break;
            case Document::EVENT_NAME_BEFORE_UPDATE:
                $event = $this->createUpdateBeforeEvent();
                break;
            case Document::EVENT_NAME_AFTER_VALIDATE:
                $event = $this->createValidateAfterEvent();
                break;
            case Document::EVENT_NAME_BEFORE_VALIDATE:
                $event =$this->createValidateBeforeEvent();
                break;
            case Document::EVENT_NAME_VALIDATE_ERROR:
                $event = $this->createValidateErrorEvent();
                break;
        }

        return $event;
    }
}