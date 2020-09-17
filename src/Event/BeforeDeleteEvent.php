<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class BeforeDeleteEvent extends Event
{
    public const NAME = 'beforeDelete';
}
