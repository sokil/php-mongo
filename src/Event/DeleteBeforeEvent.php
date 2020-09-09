<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class DeleteBeforeEvent extends Event
{
    public const NAME = 'beforeDelete';
}
