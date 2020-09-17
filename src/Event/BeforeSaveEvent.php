<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class BeforeSaveEvent extends Event
{
    public const NAME = 'beforeSave';
}
