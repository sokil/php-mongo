<?php

namespace Sokil\Mongo;

/**
 * Abstract class of behavior. Every behavior class must extend it.
 * 
 * @link https://github.com/sokil/php-mongo#behaviors
 */
abstract class Behavior
{
    private $_owner;
    
    private $_options;
    
    public function __construct(array $options = array()) 
    {        
        $this->_options = $options;
    }
    
    public function setOwner($owner)
    {
        $this->_owner = $owner;
        return $this;
    }
    
    protected function getOwner()
    {
        return $this->_owner;
    }
}
