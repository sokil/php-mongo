<?php

namespace Sokil\Mongo;

class DocumentRelationTest extends \PHPUnit_Framework_TestCase
{
    private static $database;
    
    public static function setUpBeforeClass()
    {
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        self::$database = $client
            ->map(array(
                'test'  => array(
                    'source'        => '\Sokil\Mongo\DocumentRelationTest\SourceCollection',
                    'oneoneTarget'  => '\Sokil\Mongo\DocumentRelationTest\OneoneTargetCollection',
                    'onemanyTarget'  => '\Sokil\Mongo\DocumentRelationTest\OnemanyTargetCollection',
                ),
            ))
            ->getDatabase('test');
    }
    
    public function setUp() {
    }
    
    public function tearDown() {

    }
    
    public static function tearDownAfterClass() {
        
    }
    
    /**
     * A -> HAS_ONE -> B
     */
    public function testHasOne()
    {
        // collections
        $sourceCollection = self::$database->getCollection('source');
        $oneoneTargetCollection = self::$database->getCollection('oneoneTarget');
        
        // add documents
        $sourceDocument = $sourceCollection
            ->createDocument(array('param' => 'value'))
            ->save();
        
        // add target document
        $targetDocument = $oneoneTargetCollection
            ->createDocument(array(
                'source_id' => $sourceDocument->getId()
            ))
            ->save();
        
        // test
        $this->assertInstanceOf('\Sokil\Mongo\DocumentRelationTest\OneoneTargetDocument', $sourceDocument->hasOne);
        
        $this->assertEquals($targetDocument->getId(), $sourceDocument->hasOne->getId());
        
        // clear test
        $sourceCollection->delete();
        $oneoneTargetCollection->delete();
    }
    
    /**
     * B -> BELONGS -> A
     */
    public function testBelongs()
    {
        // collections
        $sourceCollection = self::$database->getCollection('source');
        $oneoneTargetCollection = self::$database->getCollection('oneoneTarget');
        
        // add documents
        $sourceDocument = $sourceCollection
            ->createDocument(array('param' => 'value'))
            ->save();
        
        // add target document
        $targetDocument = $oneoneTargetCollection
            ->createDocument(array(
                'source_id' => $sourceDocument->getId()
            ))
            ->save();
        
        // test
        $this->assertInstanceOf('\Sokil\Mongo\DocumentRelationTest\SourceDocument', $targetDocument->belongs);
        
        $this->assertEquals($sourceDocument->getId(), $targetDocument->belongs->getId());
        
        // clear test
        $sourceCollection->delete();
        $oneoneTargetCollection->delete();
    }
    
    /**
     * A -> HAS_MANY -> B
     */
    public function testHasMany()
    {
        // collections
        $sourceCollection = self::$database->getCollection('source');
        $onemanyTargetCollection = self::$database->getCollection('onemanyTarget');
        
        // add documents
        $sourceDocument = $sourceCollection
            ->createDocument(array('param' => 'value'))
            ->save();
        
        // add target documents
        $targetDocument1 = $onemanyTargetCollection
            ->createDocument(array(
                'source_id' => $sourceDocument->getId()
            ))
            ->save();
        
        // add target document
        $targetDocument2 = $onemanyTargetCollection
            ->createDocument(array(
                'source_id' => $sourceDocument->getId()
            ))
            ->save();
        
        // test        
        $this->assertArrayHasKey((string) $targetDocument1->getId(), $sourceDocument->hasMany);
        $this->assertArrayHasKey((string) $targetDocument2->getId(), $sourceDocument->hasMany);
        
        // clear test
        $sourceCollection->delete();
        $onemanyTargetCollection->delete();
    }
}