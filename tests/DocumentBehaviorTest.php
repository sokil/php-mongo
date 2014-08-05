<?php

namespace Sokil\Mongo;

class SomeBehavior extends \Sokil\Mongo\Behavior
{
    public function return42()
    {
        return 42;
    }
    
    public function getOwnerParam($name)
    {
        return $this->getOwner()->get($name);
    }
}

class DocumentBehaviorTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private static $collection;
    
    public static function setUpBeforeClass()
    {
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        $database = $client->getDatabase('test');
        
        // select collection
        self::$collection = $database->getCollection('phpmongo_test_collection');
    }
    
    
    public function testExecuteBehavior()
    {
        $document = self::$collection->createDocument(array('param' => 0));
        
        $document->attachBehavior('get42', new SomeBehavior());
        
        $this->assertEquals(42, $document->return42());
    }
    
    public function testBehaviorOwner()
    {
        $document = self::$collection->createDocument(array('param' => 42));
        
        $document->attachBehavior('someBehavior', new SomeBehavior());
        
        $this->assertEquals(42, $document->getOwnerParam('param'));
    }
}