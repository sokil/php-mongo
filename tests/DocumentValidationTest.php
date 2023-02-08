<?php

namespace Sokil\Mongo;

use Sokil\Mongo\DocumentValidationTest\ValidatorMethodDocument;
use PHPUnit\Framework\TestCase;

class DocumentValidationTest extends TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp()
    {
        // connect to mongo
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);

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
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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

    public function testIsValid_FieldInRange_Success()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'in', 'range' => array('acceptedValue1', 'acceptedValue2')),
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // required field set to valid value
        $document->set('some-field-name1', 'acceptedValue1');
        $this->assertTrue($document->isValid());
    }

    public function testIsValid_FieldInRange_Error_DefaultMessage()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'in', 'range' => array('acceptedValue1', 'acceptedValue2')),
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // required field set to wrong value - default error message
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());

        $this->assertEquals(array(
            'some-field-name' => array(
                'in' => 'Field "some-field-name" not in range of allowed values in model Sokil\Mongo\Validator\InValidator',
            )
        ), $document->getErrors());
    }

    public function testIsValid_FieldInRange_Error_CustomMessage()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'in', 'range' => array('acceptedValue1', 'acceptedValue2'), 'message' => 'not in range'),
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // required field set to wrong value - default error message
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());

        $this->assertEquals(array(
            'some-field-name' => array(
                'in' => 'not in range',
            )
        ), $document->getErrors());
    }

    /**
     * @expectedException \Sokil\Mongo\Validator\Exception
     * @expectedExceptionMessage Minimum value of range not specified
     */
    public function testIsValid_FieldBetween_minNotSpecified()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'between', 'max' => 6)
        )));

        // required field empty
        $document->set('some-field-name', '45');
        $document->isValid();
    }

    /**
     * @expectedException \Sokil\Mongo\Validator\Exception
     * @expectedExceptionMessage Maximum value of range not specified
     */
    public function testIsValid_FieldBetween_maxNotSpecified()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'between', 'min' => 6)
        )));

        // required field empty
        $document->set('some-field-name', '45');
        $document->isValid();
    }

    public function testIsValid_FieldBetween()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'between', 'min' => 6, 'max' => 14)
        )));

        $document->set('some-field-name', 1);
        $this->assertFalse($document->isValid());

        $document->set('some-field-name', 8);
        $this->assertTrue($document->isValid());

        $document->set('some-field-name', 19);
        $this->assertFalse($document->isValid());
    }

    public function testIsValid_FieldLess()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'less', 'than' => 6)
        )));

        $document->set('some-field-name', 1);
        $this->assertTrue($document->isValid());

        $document->set('some-field-name', 6);
        $this->assertFalse($document->isValid());

        $document->set('some-field-name', 9);
        $this->assertFalse($document->isValid());
    }

    public function testIsValid_FieldGreater()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'greater', 'than' => 6)
        )));

        $document->set('some-field-name', 1);
        $this->assertFalse($document->isValid());

        $document->set('some-field-name', 6);
        $this->assertFalse($document->isValid());

        $document->set('some-field-name', 9);
        $this->assertTrue($document->isValid());
    }

    public function testIsValid_NumericField()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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

    public function testIsValid_AlphaNumericField()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'alpha_numeric')
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // required field set to wrong value
        $document->set('some-field-name', '###');
        $this->assertFalse($document->isValid());

        // required field set to valid value
        $document->set('some-field-name', 'alnum34');
        $this->assertTrue($document->isValid());
    }

    public function testIsValid_NullField()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

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

    public function testIsValid_FieldLength_Is()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'length', 'is' => 5)
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // required field set to wrong value
        $document->set('some-field-name', '1234567');
        $this->assertFalse($document->isValid());
        $this->assertEquals(
            array(
                'some-field-name' => array(
                    'length' => 'Field "some-field-name" length not equal to 5 in model Sokil\\Mongo\\Validator\\LengthValidator',
                ),
            ),
            $document->getErrors()
        );

        // required field set to valid value
        $document->set('some-field-name', '12345');
        $this->assertTrue($document->isValid());
    }

    public function testIsValid_FieldLength_IsMinMax()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'length', 'is' => 5, 'min' => 98, 'max' => 110)
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // required field set to wrong value
        $document->set('some-field-name', '1234567');
        $this->assertFalse($document->isValid());

        // required field set to valid value
        $document->set('some-field-name', '12345');
        $this->assertTrue($document->isValid());
    }

    public function testIsValid_FieldLength_Min()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'length', 'min' => 5)
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // required field length set to smaller length
        $document->set('some-field-name', '123');
        $this->assertFalse($document->isValid());

        // required field length set to equal length
        $document->set('some-field-name', '12345');
        $this->assertTrue($document->isValid());

        // required field set to longer length
        $document->set('some-field-name', '1234567');
        $this->assertTrue($document->isValid());
    }

    public function testIsValid_FieldLength_Max()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'length', 'max' => 5)
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // required field set to longer length
        $document->set('some-field-name', '1234567');
        $this->assertFalse($document->isValid());

        // required field length set to equal length
        $document->set('some-field-name', '12345');
        $this->assertTrue($document->isValid());

        // required field length set to smaller length
        $document->set('some-field-name', '123');
        $this->assertTrue($document->isValid());
    }

    public function testIsValid_FieldLength_MinMax()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'length', 'min' => 5, 'max' => 7)
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // required field set to smaller of left bound
        $document->set('some-field-name', '123');
        $this->assertFalse($document->isValid());

        // required field length set to equal of left bound
        $document->set('some-field-name', '12345');
        $this->assertTrue($document->isValid());

        // required field length set to length in range
        $document->set('some-field-name', '123456');
        $this->assertTrue($document->isValid());

        // required field length set to equal of right bound
        $document->set('some-field-name', '1234567');
        $this->assertTrue($document->isValid());

        // required field length set to longer of right bound
        $document->set('some-field-name', '123456789');
        $this->assertFalse($document->isValid());
    }

    public function testIsValid_TypeValidator()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'type', array('string', 'int'))
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        $document->set('some-field-name', 4.65);
        $this->assertFalse($document->isValid());

        $document->set('some-field-name', 'hello');
        $this->assertTrue($document->isValid());

        $document->set('some-field-name', 42);
        $this->assertTrue($document->isValid());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Type not specified
     */
    public function testIsValid_TypeValidator_TypeNotSpecified()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(
                array(
                    array('some-field-name', 'type')
                )
            ));

        $document->set('some-field-name', 42);
        $document->validate();
    }

    public function testIsValid_TypeValidator_DefaultMessageForOneType()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(
                array(
                    array('some-field-name', 'type', array('int'))
                )
            ));

        $document->set('some-field-name', 'iAmNot42');
        $this->assertFalse($document->isValid());
        $this->assertEquals(
            array(
                'some-field-name' => array(
                    'type' => 'Field "some-field-name" must be of type int in Sokil\Mongo\Validator\TypeValidator',
                ),
            ),
            $document->getErrors()
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Type must be one of array, bool, callable, double, float, int, integer, long, null, numeric, object, real, resource, scalar, string
     */
    public function testIsValid_TypeValidator_UnexistedType()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(
                array(
                    array('some-field-name', 'type', 'iAmWrongType')
                )
            ));

        $document->set('some-field-name', 42);
        $document->validate();
    }

    public function testIsValid_FieldCardNumber()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('some-field-name', 'card_number')
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // required field set to non numeric value
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());

        // required field set to numeric value with wring control digit
        $document->set('some-field-name', '4024007149737767');
        $this->assertFalse($document->isValid());

        // required field set to valid value
        $document->set('some-field-name', '4024007149737768');
        $this->assertTrue($document->isValid());
    }

    public function testIsValid_FieldEmail()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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

        try {
            // additional MX check on wrong email
            $document->set('some-field-name-mx', 'user@example');
            $this->assertFalse($document->isValid());

            // additional MX check on valid email
            $document->set('some-field-name-mx', 'user@gmail.com');
            $this->assertTrue($document->isValid());
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== '') {
                throw $e;
            }
        }
    }

    public function testIsValid_FieldUrl()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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


        try {
            // additional ping check on valid but not-existed url
            $document->set('urlField-ping', 'http://some-unexisted-server424242.com');
            $this->assertFalse($document->isValid());

            // additional ping check on valid and accesible url
            $document->set('urlField-ping', 'http://i.ua/');
            $this->assertTrue($document->isValid());
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'Error getting DNS record to validated url') {
                throw $e;
            }
        }
    }

    public function testIsValid_FieldIp()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('ipField', 'ip'),
        )));

        // required field empty
        $this->assertTrue($document->isValid());

        // ip invalid
        $document->set('ipField', '42');
        $this->assertFalse($document->isValid());

        $document->set('ipField', '777.777.777.777');
        $this->assertFalse($document->isValid());

        // ip valid
        $document->set('ipField', '93.16.56.123');
        $this->assertTrue($document->isValid());
    }

    public function testIsValid_FieldValidatedByMethod_Passed()
    {
        $document = new ValidatorMethodDocument($this->collection);
        $this->assertTrue($document->set('field', 42)->isValid());
    }

    public function testIsValid_FieldValidatedByMethod_Failed()
    {
        $document = new ValidatorMethodDocument($this->collection);
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
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules', 'someFailedValidationMethod'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

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

            $this->fail('\Sokil\Mongo\Document\InvalidDocumentException must be thrown, no exception captured');
        } catch (\Sokil\Mongo\Document\InvalidDocumentException $e) {
            $this->assertEquals(
                array('field' => array('rule' => 'message')), $document->getErrors()
            );
        } catch (\Exception $e) {
            $this->fail('\Sokil\Mongo\Document\InvalidDocumentException expected, ' . get_class($e) . ' found');
        }
    }

    public function testClearTriggeredErrors()
    {
        $document = $this->collection
            ->createDocument()
            ->triggerError('someField', 'someRule', 'someMessage');

        $this->assertTrue($document->hasErrors());

        $document->clearTriggeredErrors();

        $this->assertFalse($document->hasErrors());
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

            $this->fail('\Sokil\Mongo\Document\InvalidDocumentException must be thrown, no exception captured');
        } catch (\Sokil\Mongo\Document\InvalidDocumentException $e) {
            $this->assertEquals($errors, $document->getErrors());
        } catch (\Exception $e) {
            $this->fail('\Sokil\Mongo\Document\InvalidDocumentException expected, ' . get_class($e) . ' found');
        }
    }

    public function testAddErrors()
    {
        $errors = array(
            'field1' => array('rule1' => 'message1'),
            'field2' => array('rule2' => 'message2')
        );

        $document = new \Sokil\Mongo\Document($this->collection);
        $document->addErrors($errors);

        $this->assertEquals($errors, $document->getErrors());
    }

    public function testGetInvalidDocumentFromException()
    {
        // mock of document
        $document = $this->getMockBuilder('\Sokil\Mongo\Document')->setMethods(array('rules'))->setConstructorArgs(array($this->collection))->getMock();
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
        } catch (\Sokil\Mongo\Document\InvalidDocumentException $e) {
            $this->assertEquals($document, $e->getDocument());
        }
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Validator class must implement \Sokil\Mongo\Validator class
     */
    public function testIsValid_checkValidatorSuperclass()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                    array('field', 'wrong_superclass')
        )));

        $document
            ->set('field', '42')
            ->addValidatorNamespace('\Sokil\Mongo\DocumentValidationTest\Validator');

        $document->isValid();
    }

    public function testIsValid_ValidatorClass()
    {
        // mock of document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('field', 'my_own_equals', 'to' => 42)
            )));

        $document
            ->set('field', '42')
            ->addValidatorNamespace('\Sokil\Mongo\DocumentValidationTest\Validator');

        $document->isValid();
    }
}
