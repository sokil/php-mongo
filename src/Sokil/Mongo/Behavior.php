<?php

namespace Sokil\Mongo;

abstract class Behavior
{
    private $_options;
    
    public function __construct(array $options = array()) 
    {
        $this->_options = $options;
    }
}