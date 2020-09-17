<?php

namespace Sokil\Mongo;

class DocumentWithAfterConstructEvent extends Document
{
    public $status;

    public $param = 42;

    public function beforeConstruct()
    {
        $that = $this;

        $this->onAfterConstruct(function(Event $event) use($that) {

            $that->status = $event->getTarget()->param;
        });
    }
}
use PHPUnit\Framework\TestCase;

class DocumentEventTest extends TestCase
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

    public function testOnAfterConstruct()
    {
        $collectionMock = $this
            ->getMockBuilder('\Sokil\Mongo\Collection')
            ->setMethods(array('getDocumentClassName'))
            ->setConstructorArgs(array($this->collection->getDatabase(), 'phpmongo_test_collection'))
            ->getMock();

        $collectionMock
            ->expects($this->once())
            ->method('getDocumentClassName')
            ->will($this->returnValue('\Sokil\Mongo\DocumentWithAfterConstructEvent'));

        $document = $collectionMock->createDocument();

        $this->assertEquals(42, $document->status);

    }

    public function testOnBeforeAfterValidate()
    {
        $documentMock = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array(
                $this->collection,
                array(
                    'e' => 'user@gmail.com',
                )
            ))
            ->getMock();

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
        $documentMock = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array(
                $this->collection,
                array(
                    'e' => 'wrongEmail',
                ))
            )
            ->getMock();

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
        } catch(\Sokil\Mongo\Document\InvalidDocumentException $e) {
            $this->assertEquals('error', $documentMock->status);
        }
    }

    public function testBeforeInsert()
    {
        $status = new \stdclass;
        $status->done = false;

        $document = $this->collection->createDocument(array(
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

        $document = $this->collection->createDocument(array(
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

        $document = $this->collection
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

        $document = $this->collection
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

        $document = $this->collection
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

        $document = $this->collection
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

        $document = $this->collection
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

        $document = $this->collection
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
        $document = $this->collection
            ->createDocument(array(
                'p' => 'v'
            ));

        $document->attachEvent('someEventName', function() {});

        $this->assertTrue($document->hasEvent('someEventName'));

        $this->assertFalse($document->hasEvent('someUNEXISTEDEventName'));
    }

    public function testCancelledEventHandlerNotPropageted()
    {
        $testCase = $this;

        $status = new \stdClass;
        $status->done = false;

        $this->collection
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
        $this->collection
            ->delete()
            ->createDocument(array('field' => 'value'))
            ->onBeforeInsert(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) {
                $event->cancel();
            })
            ->save();

        $this->assertEquals(0, $this->collection->count());
    }

    public function testCancelOperation_BeforeUpdate()
    {
        $document = $this->collection
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
            $this->collection
                ->getDocumentDirectly($document->getId())
                ->get('field')
        );
    }

    public function testCancelOperation_BeforeSave()
    {
        $this->collection
            ->delete()
            ->createDocument(array('field' => 'value'))
            ->onBeforeSave(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) {
                $event->cancel();
            })
            ->save();

        $this->assertEquals(0, $this->collection->count());
    }

    public function testCancelOperation_BeforeDelete()
    {
        $document = $this->collection
            ->delete()
            ->createDocument(array('field' => 'value'))
            ->save()
            ->onBeforeDelete(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) {
                $event->cancel();
            })
            ->delete();

        $this->assertEquals(1, $this->collection->count());
    }

    public function testCancelOperation_BeforeValidate()
    {
        $testCase = $this;
        $this->collection
            ->createDocument()
            ->onBeforeValidate(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) {
                $event->cancel();
            })
            ->onAfterValidate(function(\Sokil\Mongo\Event $event, $eventName, $dispatcher) use($testCase) {
                $testCase->fail('Validation must be cancelled');
            })
            ->validate();
    }

    /**
     * Event dispatcher now shared between documents, so adding listener to one document MUST NOT fire event on other
     * After removing document its listeners also must be removed
     */
    public function testEventRaisedOnlyOnValidTarget()
    {
        throw new \RuntimeException('Not implemented');
    }
}
