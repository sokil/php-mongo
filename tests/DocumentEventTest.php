<?php

namespace Sokil\Mongo;

class DocumentWithAfterConstructEvent extends Document
{
    public $status;

    public function beforeConstruct()
    {
        $that = $this;
        $this->onAfterConstruct(function() use($that) {
            $that->status = true;
        });
    }
}

class DocumentEventTest extends \PHPUnit_Framework_TestCase
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
    
    public function setUp() 
    {
        self::$collection->delete();
    }
    
    public function tearDown() {

    }
    
    public static function tearDownAfterClass() {
        self::$collection->delete();
    }

    public function testOnAfterConstruct()
    {
        $collectionMock = $this->getMock(
            '\Sokil\Mongo\Collection',
            array('getDocumentClassName'),
            array(self::$collection->getDatabase(), 'phpmongo_test_collection')
        );

        $collectionMock
            ->expects($this->once())
            ->method('getDocumentClassName')
            ->will($this->returnValue('\Sokil\Mongo\DocumentWithAfterConstructEvent'));

        $document = $collectionMock->createDocument();

        $this->assertEquals(true, $document->status);

    }

    public function testOnBeforeAfterValidate()
    {
        $documentMock = $this->getMock(
            '\Sokil\Mongo\Document',
            array('rules'),
            array(self::$collection, array(
                'e' => 'user@gmail.com',
            ))
        );

        $documentMock
            ->expects($this->once())
            ->method('rules')
            ->will($this->returnValue(
                array(
                    array('e', 'email', 'mx' => false),
                )
            ));

        $documentMock
            ->onBeforeValidate(function( $event, $eventName, $eventDispatcher) {
                $event->getTarget()->status .= 'a';
            })
            ->onAfterValidate(function( $event, $eventName, $eventDispatcher) {
                $event->getTarget()->status .= 'b';
            });

        $documentMock->validate();

        $this->assertEquals('ab', $documentMock->status);

    }

    public function testOnValidateError()
    {
        $documentMock = $this->getMock(
            '\Sokil\Mongo\Document',
            array('rules'),
            array(self::$collection, array(
                'e' => 'wrongEmail',
            ))
        );

        $documentMock
            ->expects($this->once())
            ->method('rules')
            ->will($this->returnValue(
                array(
                    array('e', 'email', 'mx' => false),
                )
            ));

        $documentMock->onValidateError(function($e) {
            $e->getTarget()->status = 'error';
        });

        try {
            $documentMock->validate();
            $this->fail('Must be validate exception');
        } catch(\Sokil\Mongo\Document\Exception\Validate $e) {
            $this->assertEquals('error', $documentMock->status);
        }
    }

    public function testBeforeInsert()
    {
        $status = new \stdclass;
        $status->done = false;
        
        $document = self::$collection->createDocument(array(
            'p' => 'v'
        ));
        $document->onBeforeInsert(function() use($status) {
            $status->done = true;
        });
        
        $document->save();
        
        $this->assertTrue($status->done);
    }
    
    public function testAfterInsert()
    {
        $status = new \stdclass;
        $status->done = false;
        
        $document = self::$collection->createDocument(array(
            'p' => 'v'
        ));
        $document->onAfterInsert(function() use($status) {
            $status->done = true;
        });
        
        $document->save();
        
        $this->assertTrue($status->done);
    }
    
    public function testBeforeUpdate()
    {
        $status = new \stdclass;
        $status->done = false;
        
        $document = self::$collection
            ->createDocument(array(
                'p' => 'v'
            ));
        
        $document->onBeforeUpdate(function() use($status) {
            $status->done = true;
        });
        
        // insert
        $document->save();
        
        // update
        $document->set('p', 'updated')->save();
        
        $this->assertTrue($status->done);
    }
    
    public function testAfterUpdate()
    {
        $status = new \stdclass;
        $status->done = false;
        
        $document = self::$collection
            ->createDocument(array(
                'p' => 'v'
            ));
        
        $document->onAfterUpdate(function() use($status) {
            $status->done = true;
        });
        
        // insert
        $document->save();
        
        // update
        $document->set('p', 'updated')->save();
        
        $this->assertTrue($status->done);
    }
    
    public function testBeforeSave()
    {
        $status = new \stdclass;
        $status->done = false;
        
        $document = self::$collection
            ->createDocument(array(
                'p' => 'v'
            ));
        
        $document->onBeforeSave(function($event) use($status) {
            $status->done = true;
        });
        
        // insert
        $document->save();
        
        $this->assertTrue($status->done);
    }
    
    public function testAfterSave()
    {
        $status = new \stdclass;
        $status->done = false;
        
        $document = self::$collection
            ->createDocument(array(
                'p' => 'v'
            ));
        
        $document->onAfterSave(function() use($status) {
            $status->done = true;
        });
        
        // insert
        $document->save();
        
        $this->assertTrue($status->done);
    }
    
    public function testBeforeDelete()
    {
        $status = new \stdclass;
        $status->done = false;
        
        $document = self::$collection
            ->createDocument(array(
                'p' => 'v'
            ))
            ->save();
        
        $document->onBeforeDelete(function() use($status) {
            $status->done = true;
        });
        
        $document->delete();
        
        $this->assertTrue($status->done);
    }
    
    public function testAfterDelete()
    {
        $status = new \stdclass;
        $status->done = false;
        
        $document = self::$collection
            ->createDocument(array(
                'p' => 'v'
            ))
            ->save();
        
        $document->onAfterDelete(function() use($status) {
            $status->done = true;
        });
        
        $document->delete();
        
        $this->assertTrue($status->done);
    }


    public function testAttachEvent()
    {
        $document = self::$collection
            ->createDocument(array(
                'p' => 'v'
            ));

        $document->attachEvent('someEventName', function() {});

        $this->assertTrue($document->hasEvent('someEventName'));

        $this->assertFalse($document->hasEvent('someUNEXISTEDEventName'));
    }

    public function testTriggerEvent()
    {
        $status = new \stdclass;
        $status->done = false;

        $document = self::$collection
            ->createDocument(array(
                'p' => 'v'
            ));

        $document->attachEvent('someEventName', function() use($status) {
            $status->done = true;
        });

        $document->triggerEvent('someEventName');

        $this->assertTrue($status->done);
    }
    
    public function testCancelledEventHandlerNotPropageted()
    {
        $testCase = $this;
        
        $status = new \stdClass;
        $status->done = false;
        
        self::$collection
            ->createDocument()
            ->onBeforeInsert(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) use($status) {
                $status->done = true;
                $event->cancel();
            })
            ->onBeforeInsert(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) use($testCase) {
                $testCase->fail('Event propagation not stoped on event handling cancel');
            })
            ->save();
            
        $this->assertTrue($status->done);
    }
    
    public function testCancelOperation_BeforeInsert()
    {
        self::$collection
            ->delete()
            ->createDocument(array('field' => 'value'))
            ->onBeforeInsert(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) {
                $event->cancel();
            })
            ->save();
            
        $this->assertEquals(0, self::$collection->count());
    }
    
    public function testCancelOperation_BeforeUpdate()
    {
        $document = self::$collection
            ->delete()
            ->createDocument(array('field' => 'value'))
            ->save()
            ->onBeforeUpdate(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) {
                $event->cancel();
            })
            ->set('field', 'updatedValue')
            ->save();
            
        $this->assertEquals(
            'value', 
            self::$collection
                ->getDocumentDirectly($document->getId())
                ->get('field')
        );
    }
    
    public function testCancelOperation_BeforeSave()
    {
        self::$collection
            ->delete()
            ->createDocument(array('field' => 'value'))
            ->onBeforeSave(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) {
                $event->cancel();
            })
            ->save();
            
        $this->assertEquals(0, self::$collection->count());
    }
    
    public function testCancelOperation_BeforeDelete()
    {
        $document = self::$collection
            ->delete()
            ->createDocument(array('field' => 'value'))
            ->save()
            ->onBeforeDelete(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) {
                $event->cancel();
            })
            ->delete();
            
        $this->assertEquals(1, self::$collection->count());
    }
    
    public function testCancelOperation_BeforeValidate()
    {
        $documentMock = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('isValid'))
            ->setConstructorArgs(array(self::$collection))
            ->getMock();
        
        $documentMock
            ->expects($this->never())
            ->method('isValid');
        
        $documentMock
            ->onBeforeValidate(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) {
                $event->cancel();
            })
            ->save();
    }
}