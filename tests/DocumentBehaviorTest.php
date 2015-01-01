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
    private $collection;
    
    public function setUp()
    {
        // connect to mongo
        $client = new Client();
        
        // select database
        $database = $client->getDatabase('test');
        
        // select collection
        $this->collection = $database->getCollection('phpmongo_test_collection');
    }
    
    public function tearDown()
    {
        $this->collection->delete();
    }
    
    public function testExecuteBehavior()
    {
        $document = $this->collection->createDocument(array('param' => 0));

        $document->attachBehavior('get42', new SomeBehavior());
        
        $this->assertEquals(42, $document->return42());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Document has no method "unexistedMethod"
     */
    public function test__call_MethodNotFound()
    {
        $document = $this->collection->createDocument(array('param' => 0));
        $document->attachBehavior('get42', new SomeBehavior());

        $document->unexistedMethod();
    }
    
    public function testBehaviorOwner()
    {
        $document = $this->collection->createDocument(array('param' => 42));
        
        $document->attachBehavior('someBehavior', new SomeBehavior());
        
        $this->assertEquals(42, $document->getOwnerParam('param'));
    }

    public function testAttachBehaviors_AsInstanceOfbehaviorClass()
    {
        $document = $this->collection->createDocument(array('param' => 0));
        $document->attachBehaviors(array(
            'get42' => new SomeBehavior(),
        ));
    }

    public function testAttachBehaviors_AsArray()
    {
        $document = $this->collection->createDocument(array('param' => 0));
        $document->attachBehaviors(array(
            'get42' => array(
                'class' => '\Sokil\Mongo\SomeBehavior',
                'param' => 'value',
            )
        ));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Behavior class not specified
     */
    public function testAttachBehaviors_AsArray_ClassNotSpecified()
    {
        $document = $this->collection->createDocument(array('param' => 0));
        $document->attachBehaviors(array(
            'get42' => array(
                'param' => 'value',
            )
        ));
    }

    public function testBehaviorInMapping()
    {
        $db = $this->collection->getDatabase();

        $db->map('col', array(
            'behaviors' => array(
                'get42' => new SomeBehavior(),
            ),
        ));
        
        $document = $db->getCollection('col')->createDocument();
        
        $this->assertEquals('42', $document->return42());
    }
}