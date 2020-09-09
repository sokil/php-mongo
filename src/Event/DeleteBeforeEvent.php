<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class DeleteBeforeEvent extends Event
{
    const NAME = 'beforeDelete';
}
