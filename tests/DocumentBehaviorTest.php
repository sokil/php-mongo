<?php

namespace Sokil\Mongo;

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
    
    public function testGetOwner()
    {
        $document = $this->collection->createDocument(
            array('param' => 42)
        );
        
        $document->attachBehavior(
            'someBehavior',
            new SomeBehavior()
        );
        
        $this->assertEquals(
            42,
            $document->returnOwnerParam('param')
        );
    }

    public function testGetOption()
    {
        $document = $this->collection->createDocument(
            array('param' => 42)
        );

        $document->attachBehavior(
            'someBehavior',
            array(
                'class' => '\Sokil\Mongo\SomeBehavior',
                'param' => 'value',
            )
        );

        $this->assertEquals(
            'value',
            $document->returnOption('param')
        );
    }

    public function testAttachBehaviors_AsClassName()
    {
        $document = $this->collection->createDocument(array('param' => 0));
        $document->attachBehaviors(array(
            'get42' => '\Sokil\Mongo\SomeBehavior',
        ));

        $this->assertEquals(42, $document->return42());
    }

    public function testAttachBehaviors_AsInstanceOfBehaviorClass()
    {
        $document = $this->collection->createDocument(array('param' => 0));
        $document->attachBehaviors(array(
            'get42' => new SomeBehavior(),
        ));

        $this->assertEquals(42, $document->return42());
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

        $this->assertEquals(42, $document->return42());
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

    public function testBehaviorInCursor()
    {
        $db = $this->collection->getDatabase();
        $db->map('col', array(
            'behaviors' => array(
                'get42' => new SomeBehavior(),
            ),
        ));

        $collection = $db->getCollection('col');

        $collection->insertMultiple(
            array(
                array('key' => 'value1'),
                array('key' => 'value2'),
                array('key' => 'value3'),
                array('key' => 'value4'),
                array('key' => 'value5'),
            ),
            false
        );

        foreach($collection->find() as $document) {
            $this->assertEquals(42, $document->return42());
        }
    }

    public function testPublicMethods()
    {
        $behavior = new \Sokil\Mongo\SomeBehavior();
        $reflection = new \ReflectionClass($behavior);

        $methods = array_map(
            function($method) {
                return $method->getName();
            },
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        $this->assertEquals(
            array(
                // behaviors
                'return42',
                'returnOwnerParam',
                'returnOption',
                // internal methods
                '__construct',
                'setOwner',
            ),
            $methods
        );
    }
}

/**
 * Behavior
 */
class SomeBehavior extends \Sokil\Mongo\Behavior
{
    public function return42()
    {
        return 42;
    }

    public function returnOwnerParam($name)
    {
        return $this->getOwner()->get($name);
    }

    public function returnOption($name)
    {
        return $this->getOption($name);
    }
}