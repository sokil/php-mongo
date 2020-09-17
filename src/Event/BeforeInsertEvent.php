<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class BeforeInsertEvent extends Event
{
    public const NAME = 'beforeInsert';
}
