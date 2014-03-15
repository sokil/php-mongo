<?php

namespace Sokil\Mongo;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private static $client;
    
    public static function setUpBeforeClass()
    {
        // connect to mongo
        self::$client = new Client('mongodb://127.0.0.1');
    }
    
    public static function tearDownAfterClass() {
        
    }
    
    public function testMapDeclaredCollectionToClass()
    {
        self::$client->map([
            'db1'   => [
                'db1Collection1'  => '\Db1Collection1Class',
                'db1Collection2'  => '\Db1Collection2Class',
            ],
            'db2'   => '\Some\Class\Prefix\\',
        ]);
        
        $this->assertEquals(
            '\Db1Collection2Class',
            self::$client->getDatabase('db1')->getCollectionClassName('db1Collection2')
        );
    }
    
    public function testMapUndeclaredCollectionToClass()
    {
        self::$client->map([
            'db1'   => [
                'db1Collection1'  => '\Db1Collection1Class',
                'db1Collection2'  => '\Db1Collection2Class',
            ],
            'db2'   => '\Some\Class\Prefix\\',
        ]);
        
        $this->assertEquals(
            '\Sokil\Mongo\Collection',
            self::$client->getDatabase('db1')->getCollectionClassName('undeclaredCollection')
        );
        
        $this->assertEquals(
            '\Sokil\Mongo\Collection',
            self::$client->getDatabase('undeclaredDatabase')->getCollectionClassName('undeclaredCollection')
        );
    }
    
    public function testMapCollectionToClassPrefix()
    {
        self::$client->map([
            'db1'   => [
                'db1Collection1'  => '\Db1Collection1Class',
                'db1Collection2'  => '\Db1Collection2Class',
            ],
            'db2'   => '\Some\Class\Prefix\\',
        ]);
        
        $this->assertEquals(
            '\Some\Class\Prefix\Some\Collection\Name',
            self::$client->getDatabase('db2')->getCollectionClassName('some.collection.name')
        );
    }
    
    public function testUseDatabase()
    {
        $collection = self::$client
            ->useDatabase('test')
            ->getCollection('some-collection');
        
        $this->assertInstanceOf('\Sokil\Mongo\Collection', $collection);
    }
}