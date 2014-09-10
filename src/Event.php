<?php

namespace Sokil\Mongo;

class Event extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var mixed $target target object, on which event is fired
     */
    private $_target;

    /**
     * Set target object, on which event is fired
     * @param mixed $target
     * @return \Sokil\Mongo\Event
     */
    public function setTarget($target)
    {
        $this->_target = $target;
        return $this;
    }

    /**
     * Get target object, on which event is fired
     * @return mixed
     */
    public function getTarget()
    {
        return $this->_target;
    }
}