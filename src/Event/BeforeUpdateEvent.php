<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class BeforeUpdateEvent extends Event
{
    public const NAME = 'beforeUpdate';
}
