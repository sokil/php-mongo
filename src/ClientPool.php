<?php

namespace Sokil\Mongo;

/**
 * Pool of mongo connections. May be used if required few connections to different databases
 * 
 * @link https://github.com/sokil/php-mongo/blob/master/README.md#pool-of-connections
 */
class ClientPool
{
    private $_pool = array();
    
    private $_configuration;
    
    public function __construct(array $configuration = array())
    {
        $this->_configuration = $configuration;
    }
    
    public function addConnection($name, $dsn, $mapping = null, $defaultDatabase = null)
    {
        $this->_configuration[$name] = array(
            'dsn'               => $dsn,
            'defaultDatabase'   => $defaultDatabase,
            'mapping'           => $mapping,
        );
        
        return $this;
    }
    
    public function __get($name)
    {
        return $this->get($name);
    }
    
    /**
     * Get instance of connection
     * 
     * @param string $name
     * @return \Sokil\Mongo\ClientPool
     * @throws \Exception
     */
    public function get($name)
    {
        // get from cache
        if(isset($this->_pool[$name])) {
            return $this->_pool[$name];
        }
        
        // initialise
        if(!isset($this->_configuration[$name])) {
            throw new \Exception('Connection with name ' . $name . ' not found');
        }
        
        $client = new Client($this->_configuration[$name]['dsn']);
        
        if(isset($this->_configuration[$name]['mapping'])) {
            $client->map($this->_configuration[$name]['mapping']);
        }
        
        if(isset($this->_configuration[$name]['defaultDatabase'])) {
            $client->useDatabase($this->_configuration[$name]['defaultDatabase']);
        }
        
        $this->_pool[$name] = $client;
        
        return $client;
    }
}
