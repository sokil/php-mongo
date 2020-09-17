<?php

declare(strict_types=1);

namespace Sokil\Mongo\Event\Builder;

use Sokil\Mongo\Event\AfterConstructEvent;
use Sokil\Mongo\Event\AfterDeleteEvent;
use Sokil\Mongo\Event\BeforeDeleteEvent;
use Sokil\Mongo\Event\AfterInsertEvent;
use Sokil\Mongo\Event\BeforeInsertEvent;
use Sokil\Mongo\Event\AfterSaveEvent;
use Sokil\Mongo\Event\BeforeSaveEvent;
use Sokil\Mongo\Event\AfterUpdateEvent;
use Sokil\Mongo\Event\BeforeUpdateEvent;
use Sokil\Mongo\Event\AfterValidateEvent;
use Sokil\Mongo\Event\BeforeValidateEvent;
use Sokil\Mongo\Event\Builder\EventBuilderInterface;
use Sokil\Mongo\Event\ValidateErrorEvent;

/**
 * Default implementation of events.
 * For using custom events make your own implementation.
 */
class EventBuilder implements EventBuilderInterface
{
    public function buildAfterConstructEvent() : AfterConstructEvent
    {
        return new AfterConstructEvent();
    }

    public function buildAfterDeleteEvent() : AfterDeleteEvent
    {
        return new AfterDeleteEvent();
    }

    public function buildBeforeDeleteEvent() : BeforeDeleteEvent
    {
        return new BeforeDeleteEvent();
    }

    public function buildAfterInsertEvent() : AfterInsertEvent
    {
        return new AfterInsertEvent();
    }

    public function buildBeforeInsertEvent() : BeforeInsertEvent
    {
        return new BeforeInsertEvent();
    }

    public function buildAfterSaveEvent() : AfterSaveEvent
    {
        return new AfterSaveEvent();
    }

    public function buildBeforeSaveEvent() : BeforeSaveEvent
    {
        return new BeforeSaveEvent();
    }

    public function buildAfterUpdateEvent() : AfterUpdateEvent
    {
        return new AfterUpdateEvent();
    }

    public function buildBeforeUpdateEvent() : BeforeUpdateEvent
    {
        return new BeforeUpdateEvent();
    }

    public function buildAfterValidateEvent() : AfterValidateEvent
    {
        return new AfterValidateEvent();
    }

    public function buildBeforeValidateEvent() : BeforeValidateEvent
    {
        return new BeforeValidateEvent();
    }

    public function buildValidateErrorEvent() : ValidateErrorEvent
    {
        return new ValidateErrorEvent();
    }
}