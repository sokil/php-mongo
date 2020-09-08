<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class UpdateBeforeEvent extends Event
{
    public const NAME = 'beforeUpdate';
}
