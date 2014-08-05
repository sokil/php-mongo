<?php

namespace Sokil\Mongo;

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
    
    public function setUp() {
    }
    
    public function tearDown() {

    }
    
    public static function tearDownAfterClass() {
        self::$collection->delete();
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
}