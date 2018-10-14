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
 * Pool of mongo connections. May be used if required few connections to different databases
 *
 * @link https://github.com/sokil/php-mongo/blob/master/README.md#pool-of-connections
 */
class ClientPool
{
    /**
     * @var array
     */
    private $pool = array();

    /**
     * Structure described in self::addConnection method
     *
     * @var array
     */
    private $configuration;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration = array())
    {
        $this->configuration = $configuration;
    }

    /**
     * Add connection to pool
     *
     * @param string $name connection identifier
     * @param string $dsn data source name
     * @param array $mapping mapping configuration
     * @param string $defaultDatabase name of database used as default
     * @param array $connectOptions connect options
     *
     * @return ClientPool
     */
    public function addConnection(
        $name,
        $dsn = null,
        array $mapping = null,
        $defaultDatabase = null,
        array $connectOptions = null
    ) {
        $this->configuration[$name] = array(
            'dsn'               => $dsn,
            'connectOptions'    => $connectOptions,
            'defaultDatabase'   => $defaultDatabase,
            'mapping'           => $mapping,
        );
        
        return $this;
    }

    /**
     * @param string $name
     *
     * @return Client
     *
     * @throws Exception
     */
    public function __get($name)
    {
        return $this->get($name);
    }
    
    /**
     * Get instance of connection
     *
     * @param string $name connection identifier
     *
     * @return Client
     *
     * @throws Exception
     */
    public function get($name)
    {
        // get from cache
        if (isset($this->pool[$name])) {
            return $this->pool[$name];
        }
        
        // check if connection exists
        if (!isset($this->configuration[$name])) {
            throw new Exception('Connection with name ' . $name . ' not found');
        }
        
        // check if dsn exists
        if (!isset($this->configuration[$name]['dsn'])) {
            $this->configuration[$name]['dsn'] = null;
        }
        
        // check if connect options exists
        if (empty($this->configuration[$name]['connectOptions'])) {
            $this->configuration[$name]['connectOptions'] = null;
        }
        
        // init client
        $client = new Client(
            $this->configuration[$name]['dsn'],
            $this->configuration[$name]['connectOptions']
        );
        
        if (isset($this->configuration[$name]['mapping'])) {
            $client->map($this->configuration[$name]['mapping']);
        }
        
        if (isset($this->configuration[$name]['defaultDatabase'])) {
            $client->useDatabase($this->configuration[$name]['defaultDatabase']);
        }
        
        $this->pool[$name] = $client;
        
        return $client;
    }
}
