<?php

namespace Sokil\Mongo\Event;

use Sokil\Mongo\Event;

class SaveBeforeEvent extends Event
{
    const NAME = 'beforeSave';
}
