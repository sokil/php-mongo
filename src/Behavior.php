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

/**
 * Abstract class of behavior. Every behavior class must extend it.
 *
 * @link https://github.com/sokil/php-mongo#behaviors
 */
abstract class Behavior
{
    private $owner;
    
    private $options;
    
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    protected function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }
    
    /**
     * @param Document $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
        return $this;
    }
    
    /**
     * @return Document
     */
    protected function getOwner()
    {
        return $this->owner;
    }
}
