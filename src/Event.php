<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo;

class Event extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var mixed $target target object, on which event is fired
     */
    private $target;
    
    private $cancelled = false;

    /**
     * Set target object, on which event is fired
     * @param mixed $target
     * @return \Sokil\Mongo\Event
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Get target object, on which event is fired
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }
    
    /**
     * Check if operation execution cancelled
     */
    public function isCancelled()
    {
        return $this->cancelled;
    }
    
    /**
     * Cancel related operation execution. If called as beforeInsert
     * handler, than insert will be cancelled.
     */
    public function cancel()
    {
        $this->cancelled = true;
        
        // propagation also not need
        $this->stopPropagation();
        
        return $this;
    }
}
