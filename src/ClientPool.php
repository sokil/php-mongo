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
    
    public function addConnection($name, $dsn = null, $mapping = null, $defaultDatabase = null, array $options = null)
    {
        $this->_configuration[$name] = array(
            'dsn'               => $dsn,
            'connectOptions'    => $options,
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
        
        // check if connection exists
        if(!isset($this->_configuration[$name])) {
            throw new Exception('Connection with name ' . $name . ' not found');
        }
        
        // check if dsn exists
        if(!isset($this->_configuration[$name]['dsn'])) {
            $this->_configuration[$name]['dsn'] = null;
        }
        
        // check if connect options exists
        if(empty($this->_configuration[$name]['connectOptions'])) {
            $this->_configuration[$name]['connectOptions'] = null;
        }
        
        // init client
        $client = new Client(
            $this->_configuration[$name]['dsn'],
            $this->_configuration[$name]['connectOptions']
        );
        
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
