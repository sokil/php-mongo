<?php

namespace Sokil\Mongo\Event\Builder;

use Sokil\Mongo\Event\AfterConstructEvent;
use Sokil\Mongo\Event\AfterDeleteEvent;
use Sokil\Mongo\Event\AfterInsertEvent;
use Sokil\Mongo\Event\AfterSaveEvent;
use Sokil\Mongo\Event\AfterUpdateEvent;
use Sokil\Mongo\Event\AfterValidateEvent;
use Sokil\Mongo\Event\BeforeDeleteEvent;
use Sokil\Mongo\Event\BeforeInsertEvent;
use Sokil\Mongo\Event\BeforeSaveEvent;
use Sokil\Mongo\Event\BeforeUpdateEvent;
use Sokil\Mongo\Event\BeforeValidateEvent;
use Sokil\Mongo\Event\ValidateErrorEvent;

interface EventBuilderInterface
{
    public function buildAfterConstructEvent(): AfterConstructEvent;

    public function buildAfterDeleteEvent(): AfterDeleteEvent;

    public function buildBeforeDeleteEvent(): BeforeDeleteEvent;

    public function buildAfterInsertEvent(): AfterInsertEvent;

    public function buildBeforeInsertEvent(): BeforeInsertEvent;

    public function buildAfterSaveEvent(): AfterSaveEvent;

    public function buildBeforeSaveEvent(): BeforeSaveEvent;

    public function buildAfterUpdateEvent(): AfterUpdateEvent;

    public function buildBeforeUpdateEvent(): BeforeUpdateEvent;

    public function buildAfterValidateEvent(): AfterValidateEvent;

    public function buildBeforeValidateEvent(): BeforeValidateEvent;

    public function buildValidateErrorEvent(): ValidateErrorEvent;
}