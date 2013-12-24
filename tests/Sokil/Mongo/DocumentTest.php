<?php

namespace Sokil\Mongo;

class DocumentTest extends \PHPUnit_Framework_TestCase
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
    
    public function setUp() {
    }
    
    public function tearDown() {

    }
    
    public static function tearDownAfterClass() {
        self::$collection->delete();
    }
    
    public function testCreateDocumentFromArray()
    {
        $document = self::$collection->createDocument(array(
            'param1'    => 'value1',
            'param2'    => array(
                'param21'   => 'value21',
                'param22'   => 'value22',
            )
        ));
        
        $this->assertEquals('value1', $document->get('param1'));
        $this->assertEquals('value22', $document->get('param2.param22'));
    }
    
    public function testSetId()
    {
        // save document
        $id = new \MongoId();
        
        $doc = self::$collection->createDocument(array('a' => 'a'));
        $doc->setId($id);
        self::$collection->saveDocument($doc);
        
        // find document
        $this->assertNotEmpty(self::$collection->getDocument($id));
        
        // delete document
        self::$collection->deleteDocument($doc);
        
    }
    
    public function testIsValid_RequiredField()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'required')
            )));
        
        // required field empty
        $this->assertFalse($document->isValid());
        
        // required field set
        $document->set('some-field-name', 'some-value');
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldEquals()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'equals', 'to' => 'some-value')
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'some-wrong-value');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 'some-value');
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldNotEquals()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'not_equals', 'to' => 'some-value')
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'some-wrong-value');
        $this->assertTrue($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 'some-value');
        $this->assertFalse($document->isValid());
    }
    
    public function testIsValid_FieldInRange()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'in', 'range' => array('acceptedValue1', 'acceptedValue2'))
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 'acceptedValue1');
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_NumericField()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'numeric')
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 23);
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_NullField()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'numeric')
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', null);
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldEqualsOnScenario()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'equals', 'to' => 23, 'on' => 'SCENARIO_1,SCENARIO_2')
            )));
        
        // required field empty
        $document->set('some-field-name', 'wrongValue');
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->setScenario('SCENARIO_1');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 23);
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldEqualsExceptScenario()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'equals', 'to' => 23, 'except' => 'SCENARIO_1,SCENARIO_2')
            )));
        
        // required field empty
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // field set to valid value
        $document->set('some-field-name', 23);
        $this->assertTrue($document->isValid());
        
        // set excepted scenario
        $document->setScenario('SCENARIO_2');
        
        // required field set to wrong value
        $document->set('some-field-name', 'wrongValue');
        $this->assertTrue($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 23);
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldRegexp()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'regexp', 'pattern' => '#[a-z]+[0-9]+[a-z]+#')
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 'abc123def');
        $this->assertTrue($document->isValid());
    }
    
    public function testIncrement()
    {
        // create document
        $doc = self::$collection->createDocument(array('i' => 100));
        self::$collection->saveDocument($doc);
        
        // increment
        $doc->increment('i', 23);
        $doc->set('j', 77);
        self::$collection->saveDocument($doc);
        
        // check
        $doc = self::$collection->getDocument($doc->getId());
        
        $this->assertEquals(123, $doc->i);
        $this->assertEquals(77, $doc->j);
    }
    
    public function testPush()
    {
        $doc = self::$collection->createDocument(array('some1' => 'some', 'some2' => 'some'));
        self::$collection->saveDocument($doc);
        
        // push single to empty
        $doc->push('key1', 1);
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(1), self::$collection->getDocument($doc->getId())->key1);
        
        // push array to empty
        $doc->push('key2', array(1));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(1), self::$collection->getDocument($doc->getId())->key2);
        
        // push single to single
        $doc->push('some1', 'another');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another'), self::$collection->getDocument($doc->getId())->some1);
        
        // push array to single
        $doc->push('some2', array('another'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another'), self::$collection->getDocument($doc->getId())->some2);
        
        // push single to array
        $doc->push('some1', 'yet another');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another', 'yet another'), self::$collection->getDocument($doc->getId())->some1);
        
        // push array to array
        $doc->push('some2', array('yet another'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another', 'yet another'), self::$collection->getDocument($doc->getId())->some2);
    }
}