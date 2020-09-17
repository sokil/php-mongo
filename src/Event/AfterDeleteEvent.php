<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class AfterDeleteEvent extends Event
{
    public const NAME = 'afterDelete';
}
