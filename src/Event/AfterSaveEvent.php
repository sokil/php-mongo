<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class AfterSaveEvent extends Event
{
    public const NAME = 'afterSave';
}
