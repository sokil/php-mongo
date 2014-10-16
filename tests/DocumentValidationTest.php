<?php

namespace Sokil\Mongo;

class DocumentValidationTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;
    
    public function setUp()
    {
        // connect to mongo
        $client = new Client(MONGO_DSN);
        
        // select database
        $database = $client->getDatabase('test');
        
        // select collection
        $this->collection = $database->getCollection('phpmongo_test_collection');
    }
    
    public function tearDown()
    {
        $this->collection->delete();
    }
    
    public function testIsValid_RequiredField()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
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
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
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
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
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
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
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
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
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
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
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
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
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
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
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
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
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

    public function testIsValid_FieldNull()
    {
        // mock of document
        $document = $this->getMock(
            '\Sokil\Mongo\Document',
            array('rules'),
            array($this->collection)
        );

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'null')
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
    
    public function testIsValid_FieldEmail()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'email'),
                array('some-field-name-mx', 'email', 'mx' => true)
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // Email invalid
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // Email valid
        $document->set('some-field-name', 'user@example.com');
        $this->assertTrue($document->isValid());
        
        // additional MX check on wrong email
        $document->set('some-field-name-mx', 'user@example.com');
        $this->assertFalse($document->isValid());
        
        // additional MX check on valid email
        $document->set('some-field-name-mx', 'user@gmail.com');
        $this->assertTrue($document->isValid());
        
    }
    
    public function testIsValid_FieldU()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('urlField', 'url'),
                array('urlField-ping', 'url', 'ping' => true)
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // Url invalid
        $document->set('urlField', '42');
        $this->assertFalse($document->isValid());
        
        // URL valid
        $document->set('urlField', 'http://example.com');
        $this->assertTrue($document->isValid());

        // additional ping check on valid but not-existed url
        $document->set('urlField-ping', 'http://some-unexisted-server424242.com');
        $this->assertFalse($document->isValid());
        
        // additional ping check on valid and accesible url
        $document->set('urlField-ping', 'http://i.ua/');
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldValidatedByMethod_Passed()
    {
        $document = new DocumentWithMethodValidator($this->collection);
        $this->assertTrue($document->set('field', 42)->isValid());
    }
    
    public function testIsValid_FieldValidatedByMethod_Failed()
    {
        $document = new DocumentWithMethodValidator($this->collection);
        $this->assertFalse($document->set('field', 43)->isValid());
        
        $this->assertEquals(array(
            'field' => array(
                'validateEquals42' => 'Not equals to 42',
            ),
        ), $document->getErrors());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Validator with name SomeUnexistedValidator not found
     */
    public function testIsValid_UnexistedValidator()
    {
        // mock of document
        $document = $this->getMock(
            '\Sokil\Mongo\Document',
            array('rules', 'someFailedValidationMethod'),
            array($this->collection)
        );

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'SomeUnexistedValidator'),
            )));

        $document->isValid();
    }
    
    public function testTriggerError()
    {
        try {
            $document = new \Sokil\Mongo\Document($this->collection);
            $document->triggerError('field', 'rule', 'message');
            
            $document->validate();
            
            $this->fail('\Sokil\Mongo\Document\Exception\Validate must be thrown, no exception captured');
        }
        catch (\Sokil\Mongo\Document\Exception\Validate $e) {
            $this->assertEquals(
                array('field' => array('rule' => 'message')), 
                $document->getErrors()
            );
        }
        catch(\Exception $e) {
            $this->fail('\Sokil\Mongo\Document\Exception\Validate expected, ' . get_class($e) . ' found');
        }
        
    }
    
    public function testTriggerErrors()
    {
        $errors = array(
            'field1' => array('rule1' => 'message1'),
            'field2' => array('rule2' => 'message2')
        );
        
        try {
            $document = new \Sokil\Mongo\Document($this->collection);
            $document->triggerErrors($errors);
            
            $document->validate();
            
            $this->fail('\Sokil\Mongo\Document\Exception\Validate must be thrown, no exception captured');
        }
        catch (\Sokil\Mongo\Document\Exception\Validate $e) {
            $this->assertEquals($errors, $document->getErrors());
        }
        catch(\Exception $e) {
            $this->fail('\Sokil\Mongo\Document\Exception\Validate expected, ' . get_class($e) . ' found');
        }
    }

    public function testGetInvalidDocumentFromException()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($this->collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'numeric')
            )));

        $document->set('some-field-name', 'wrongValue');

        try {
            $document->validate();
            $this->fail('Must be exception');
        } catch(\Sokil\Mongo\Document\Exception\Validate $e) {
            $this->assertEquals($document, $e->getDocument());
        }
    }
}

class DocumentWithMethodValidator extends \Sokil\Mongo\Document
{
    public function rules()
    {
        return array(
            array('field', 'validateEquals42'),
        );
    }
    
    public function validateEquals42($fieldName, array $params)
    {
        if(42 !== $this->get($fieldName)) {
            $errorMessage = isset($params['message'])
                ? $params['message']
                : 'Not equals to 42';
            
            $this->addError($fieldName, 'validateEquals42', $errorMessage);
        }
    }
}