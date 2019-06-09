<?php

namespace Sokil\Mongo;

use PHPUnit\Framework\TestCase;

class ClientPoolTest extends TestCase
{
    
    public function testGet()
    {
        $pool = new ClientPool(array(
            'connect1' => array(
                'dsn' => getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null,
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
                'dsn' => getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null,
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
        $method = $reflectionClass->getMethod('getCollectionDefinition');
        $method->setAccessible(true);
        $collectionClassName = $method->invoke($database, 'col2');
        
        $this->assertEquals('\Sokil\Mongo\Collection8', $collectionClassName->class);
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Connection with name unexistedConnection not found
     */
    public function testGetUnexistedConnection()
    {
        $pool = new ClientPool(array(
            'connect1' => array(
                'dsn' => getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null,
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
                'dsn' => getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null,
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
                'dsn' => getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null,
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
                'dsn' => getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null,
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
            getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null,
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
            getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null,
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
        $method = $reflectionClass->getMethod('getCollectionDefinition');
        $method->setAccessible(true);
        $collectionClassName = $method->invoke($database, 'col2');

        $this->assertEquals('\Sokil\Mongo\Collection8', $collectionClassName->class);
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