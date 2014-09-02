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
                        'col1' => '\Collection5',
                        'col2' => '\Collection6',
                    ),
                    'db2' => array(
                        'col1' => '\Collection7',
                        'col2' => '\Collection8',
                    )
                ),
            ),  
        ));
        
        $collectionClassName = $pool
            ->get('connect2')
            ->getDatabase('db2')
            ->getCollectionClassName('col2');
        
        $this->assertEquals('\Collection8', $collectionClassName);
    }
    
    public function testGetFromDefaultDb()
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
                'mappign' => array(
                    'db1' => array(
                        'col1' => '\Collection5',
                        'col2' => '\Collection6',
                    ),
                    'db2' => array(
                        'col1' => '\Collection7',
                        'col2' => '\Collection8',
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
            'mongodb://127.0.0.1',
            array(
                'db1' => array(
                    'col1' => '\Collection1',
                    'col2' => '\Collection2',
                ),
                'db2' => array(
                    'col1' => '\Collection3',
                    'col2' => '\Collection4',
                )
            ),
            'db2'
        );

        $pool->addConnection(
            'connect2',
            'mongodb://127.0.0.1',
            array(
                'db1' => array(
                    'col1' => '\Collection5',
                    'col2' => '\Collection6',
                ),
                'db2' => array(
                    'col1' => '\Collection7',
                    'col2' => '\Collection8',
                )
            ),
            'db2'
        );

        $collectionClassName = $pool
            ->get('connect2')
            ->getDatabase('db2')
            ->getCollectionClassName('col2');

        $this->assertEquals('\Collection8', $collectionClassName);
    }
}