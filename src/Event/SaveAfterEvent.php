<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class SaveAfterEvent extends Event
{
    public const NAME = 'afterSave';
}
