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
                    'cars'      => '\Sokil\Mongo\DocumentRelationTest\CarsCollection',
                    'engines'   => '\Sokil\Mongo\DocumentRelationTest\EnginesCollection',
                    'wheels'    => '\Sokil\Mongo\DocumentRelationTest\WheelsCollection',
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
    public function testGetRelations_HasOne()
    {
        // collections
        $carsCollection = self::$database->getCollection('cars');
        $enginesCollection = self::$database->getCollection('engines');
        
        // add documents
        $carDocument = $carsCollection
            ->createDocument(array('param' => 'value'))
            ->save();
        
        // add target document
        $engineDocument = $enginesCollection
            ->createDocument(array(
                'car_id' => $carDocument->getId()
            ))
            ->save();
        
        // test
        $this->assertInstanceOf('\Sokil\Mongo\DocumentRelationTest\EngineDocument', $carDocument->engine);
        
        $this->assertEquals($engineDocument->getId(), $carDocument->engine->getId());
        
        // clear test
        $carsCollection->delete();
        $enginesCollection->delete();
    }
    
    /**
     * B -> BELONGS -> A
     */
    public function testGetRelations_Belongs()
    {
        // collections
        $carsCollection = self::$database->getCollection('cars');
        $enginesCollection = self::$database->getCollection('engines');
        
        // add documents
        $carDocument = $carsCollection
            ->createDocument(array('param' => 'value'))
            ->save();
        
        // add target document
        $engineDocument = $enginesCollection
            ->createDocument(array(
                'car_id' => $carDocument->getId()
            ))
            ->save();
        
        // test
        $this->assertInstanceOf('\Sokil\Mongo\DocumentRelationTest\CarDocument', $engineDocument->car);
        
        $this->assertEquals($carDocument->getId(), $engineDocument->car->getId());
        
        // clear test
        $carsCollection->delete();
        $enginesCollection->delete();
    }
    
    /**
     * A -> HAS_MANY -> B
     */
    public function testGetRelations_HasMany()
    {
        // collections
        $carsCollection = self::$database->getCollection('cars');
        $wheelsCollection = self::$database->getCollection('wheels');
        
        // add documents
        $carDocument = $carsCollection
            ->createDocument(array('param' => 'value'))
            ->save();
        
        // add target documents
        $wheelDocument1 = $wheelsCollection
            ->createDocument(array(
                'car_id' => $carDocument->getId()
            ))
            ->save();
        
        // add target document
        $wheelDocument2 = $wheelsCollection
            ->createDocument(array(
                'car_id' => $carDocument->getId()
            ))
            ->save();
        
        // test        
        $this->assertArrayHasKey((string) $wheelDocument1->getId(), $carDocument->wheels);
        $this->assertArrayHasKey((string) $wheelDocument2->getId(), $carDocument->wheels);
        
        // clear test
        $carsCollection->delete();
        $wheelsCollection->delete();
    }
    
    public function testGetRelations_Belongs_Cache()
    {
        // collections
        $carsCollection = self::$database->getCollection('cars');
        $wheelsCollection = self::$database->getCollection('wheels');
        
        // add documents
        $carDocument = $carsCollection
            ->createDocument(array('param' => 'value'))
            ->save();
        
        // add target documents
        $wheelDocument1 = $wheelsCollection
            ->createDocument(array(
                'car_id' => $carDocument->getId()
            ))
            ->save();
        
        // add target document
        $wheelDocument2 = $wheelsCollection
            ->createDocument(array(
                'car_id' => $carDocument->getId()
            ))
            ->save();
        
        // modify property on car, linked to wheel1
        $wheelDocument1->car->color = 'red';
        
        // test if same object        
        $this->assertEquals('red', $wheelDocument2->car->color);
        
        // clear test
        $carsCollection->delete();
        $wheelsCollection->delete();
    }
}