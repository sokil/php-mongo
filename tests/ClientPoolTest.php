<?php

namespace Sokil\Mongo;

class ClientPoolTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $pool = new ClientPool(array(
            'connect1' => array(
                'dsn' => 'mongodb://127.0.0.1',
                'defaultDatabase' => 'db2',
                'mapping' => array(
                    'db1' => array(
                        'col1' => '\Collection1',
                        'col2' => '\Collection2',
                    ),
                    'db2' => array(
                        'col1' => '\Collection3',
                        'col2' => '\Collection4',
                    )
                ),
            ),
            'connect2' => array(
                'dsn' => 'mongodb://127.0.0.1',
                'defaultDatabase' => 'db2',
                'mapping' => array(
                    'db1' => array(
                        'col1' => '\Sokil\Mongo\Collection5',
                        'col2' => '\Sokil\Mongo\Collection6',
                    ),
                    'db2' => array(
                        'col1' => '\Sokil\Mongo\Collection7',
                        'col2' => '\Sokil\Mongo\Collection8',
                    )
                ),
            ),  
        ));

        $this->assertInstanceOf('\Sokil\Mongo\Client', $pool->get('connect2'));

        $this->assertInstanceOf('\Sokil\Mongo\Client', $pool->connect2);

        $database = $pool
            ->get('connect2')
            ->getDatabase('db2');
        
        $reflectionClass = new \ReflectionClass($database);
        $method = $reflectionClass->getMethod('getCollectionClassDefinition');
        $method->setAccessible(true);
        $collectionClassName = $method->invoke($database, 'col2');
        
        $this->assertEquals('\Sokil\Mongo\Collection8', $collectionClassName['class']);
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Connection with name unexistedConnection not found
     */
    public function testGetUnexistedConnection()
    {
        $pool = new ClientPool(array(
            'connect1' => array(
                'dsn' => 'mongodb://127.0.0.1',
                'defaultDatabase' => 'db2',
                'mapping' => array(
                    'db1' => array(
                        'col1' => '\Sokil\Mongo\Collection1',
                        'col2' => '\Sokil\Mongo\Collection2',
                    ),
                    'db2' => array(
                        'col1' => '\Sokil\Mongo\Collection3',
                        'col2' => '\Sokil\Mongo\Collection4',
                    )
                ),
            ),
            'connect2' => array(
                'dsn' => 'mongodb://127.0.0.1',
                'defaultDatabase' => 'db2',
                'mapping' => array(
                    'db1' => array(
                        'col1' => '\Sokil\Mongo\Collection5',
                        'col2' => '\Sokil\Mongo\Collection6',
                    ),
                    'db2' => array(
                        'col1' => '\Sokil\Mongo\Collection7',
                        'col2' => '\Sokil\Mongo\Collection8',
                    )
                ),
            ),
        ));

        $pool->unexistedConnection;
    }
    
    public function testGetFromDefaultDb()
    {
        $pool = new ClientPool(array(
            'connect1' => array(
                'dsn' => 'mongodb://127.0.0.1',
                'defaultDatabase' => 'db2',
                'mapping' => array(
                    'db1' => array(
                        'col1' => '\Sokil\Mongo\Collection1',
                        'col2' => '\Sokil\Mongo\Collection2',
                    ),
                    'db2' => array(
                        'col1' => '\Sokil\Mongo\Collection3',
                        'col2' => '\Sokil\Mongo\Collection4',
                    )
                ),
            ),
            'connect2' => array(
                'dsn' => 'mongodb://127.0.0.1',
                'defaultDatabase' => 'db2',
                'mappign' => array(
                    'db1' => array(
                        'col1' => '\Sokil\Mongo\Collection5',
                        'col2' => '\Sokil\Mongo\Collection6',
                    ),
                    'db2' => array(
                        'col1' => '\Sokil\Mongo\Collection7',
                        'col2' => '\Sokil\Mongo\Collection8',
                    )
                ),
            ),  
        ));
        
        $collection = $pool
            ->get('connect2')
            ->getCollection('col2');
        
        $this->assertInstanceOf('\Sokil\Mongo\Collection', $collection);
    }

    public function testAddConnection()
    {
        $pool = new ClientPool();

        $pool->addConnection(
            'connect1',
            null,
            array(
                'db1' => array(
                    'col1' => '\Sokil\Mongo\Collection1',
                    'col2' => '\Sokil\Mongo\Collection2',
                ),
                'db2' => array(
                    'col1' => '\Sokil\Mongo\Collection3',
                    'col2' => '\Sokil\Mongo\Collection4',
                )
            ),
            'db2'
        );

        $pool->addConnection(
            'connect2',
            null,
            array(
                'db1' => array(
                    'col1' => '\Sokil\Mongo\Collection5',
                    'col2' => '\Sokil\Mongo\Collection6',
                ),
                'db2' => array(
                    'col1' => '\Sokil\Mongo\Collection7',
                    'col2' => '\Sokil\Mongo\Collection8',
                )
            ),
            'db2'
        );

        $database = $pool
            ->get('connect2')
            ->getDatabase('db2');
        
        $reflectionClass = new \ReflectionClass($database);
        $method = $reflectionClass->getMethod('getCollectionClassDefinition');
        $method->setAccessible(true);
        $collectionClassName = $method->invoke($database, 'col2');

        $this->assertEquals('\Sokil\Mongo\Collection8', $collectionClassName['class']);
    }

    public function testGet_DsnNotSpecified()
    {
        $pool = new ClientPool(array(
            'connect1' => array(
                'defaultDatabase' => 'db2',
                'mapping' => array(
                    'db1' => array(
                        'col1' => '\Sokil\Mongo\Collection1',
                        'col2' => '\Sokil\Mongo\Collection2',
                    ),
                    'db2' => array(
                        'col1' => '\Sokil\Mongo\Collection3',
                        'col2' => '\Sokil\Mongo\Collection4',
                    )
                ),
            )
        ));

        $this->assertEquals(Client::DEFAULT_DSN, $pool->get('connect1')->getDsn());
    }
}

class Collection1 extends \Sokil\Mongo\Collection {}
class Collection2 extends \Sokil\Mongo\Collection {}
class Collection3 extends \Sokil\Mongo\Collection {}
class Collection4 extends \Sokil\Mongo\Collection {}
class Collection5 extends \Sokil\Mongo\Collection {}
class Collection6 extends \Sokil\Mongo\Collection {}
class Collection7 extends \Sokil\Mongo\Collection {}
class Collection8 extends \Sokil\Mongo\Collection {}