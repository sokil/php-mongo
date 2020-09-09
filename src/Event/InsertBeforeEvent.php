<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class InsertBeforeEvent extends Event
{
    public const NAME = 'beforeInsert';
}
