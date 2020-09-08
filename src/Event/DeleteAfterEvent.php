<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class DeleteAfterEvent extends Event
{
    public const NAME = 'afterDelete';
}
