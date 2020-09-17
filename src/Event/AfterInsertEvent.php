<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class AfterInsertEvent extends Event
{
    public const NAME = 'afterInsert';
}
