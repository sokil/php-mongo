<?php

declare(strict_types=1);

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo;

use Psr\EventDispatcher\StoppableEventInterface;

class Event implements StoppableEventInterface
{
    /**
     * @var mixed $target target object, on which event is fired
     */
    private $target;

    private $cancelled = false;

    private $propagationStopped = false;

    /**
     * {@inheritdoc}
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Set target object, on which event is fired
     *
     * @param mixed $target
     *
     * @return Event
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
