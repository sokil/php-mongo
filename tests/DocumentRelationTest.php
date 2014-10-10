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
    
    public function testAddRelation_Belongs()
    {
        // collections
        $carsCollection = self::$database->getCollection('cars');
        $wheelsCollection = self::$database->getCollection('wheels');
        
        // add documents
        $carDocument = $carsCollection
            ->createDocument(array('brand' => 'Nissan'))
            ->save();
        
        // add target documents
        $wheelDocument = $wheelsCollection
            ->createDocument(array(
                'diameter' => 30,
            ))
            ->addRelation('car', $carDocument)
            ->save();
        
        $this->assertEquals($carDocument->getId(), $wheelDocument->car_id);
    }
    
    public function testAddRelation_HasOne()
    {
        // collections
        $carsCollection = self::$database->getCollection('cars');
        $enginesCollection = self::$database->getCollection('engines');
        
        $engineDocument = $enginesCollection
            ->createDocument(array(
                'power' => 300,
            ))
            ->save();

        $carDocument = $carsCollection
            ->createDocument(array('brand' => 'Nissan'))
            ->save()
            ->addRelation('engine', $engineDocument);
        
        $this->assertEquals($carDocument->getId(), $engineDocument->car_id);
    }
    
    public function testAddRelation_HasMany()
    {
        // collections
        $carsCollection = self::$database->getCollection('cars');
        $wheelsCollection = self::$database->getCollection('wheels');
        
        $wheelDocument = $wheelsCollection
            ->createDocument(array(
                'diameter' => 30,
            ))
            ->save();

        $carDocument = $carsCollection
            ->createDocument(array('brand' => 'Nissan'))
            ->save()
            ->addRelation('wheels', $wheelDocument);
        
        $this->assertEquals($carDocument->getId(), $wheelDocument->car_id);
    }
    
    public function testRemoveRelation_Belongs()
    {
        // collections
        $carsCollection = self::$database->getCollection('cars');
        $wheelsCollection = self::$database->getCollection('wheels');
        
        // add documents
        $carDocument = $carsCollection
            ->createDocument(array('brand' => 'Nissan'))
            ->save();
        
        // add target documents
        $wheelDocument = $wheelsCollection
            ->createDocument(array(
                'diameter' => 30,
                'car_id' => $carDocument->getId(),
            ))
            ->save();
        
        // remove relation
        $wheelDocument->removeRelation('car');
        
        // check
        $this->assertEmpty($wheelDocument->car_id);
    }
    
    public function testRemoveRelation_HasOne()
    {
        // collections
        $carsCollection = self::$database->getCollection('cars');
        $enginesCollection = self::$database->getCollection('engines');
        
        $carDocument = $carsCollection
            ->createDocument(array('brand' => 'Nissan'))
            ->save();
        
        $engineDocument = $enginesCollection
            ->createDocument(array(
                'car_id' => $carDocument->getId(),
            ))
            ->save();

        $carDocument->removeRelation('engine');
        
        $this->assertEmpty(
            $enginesCollection
                ->getDocumentDirectly($engineDocument->getId())
                ->car_id
        );
    }
    
    public function testRemoveRelation_HasMany()
    {
        // collections
        $carsCollection = self::$database->getCollection('cars');
        $wheelsCollection = self::$database->getCollection('wheels');
        
        $carDocument = $carsCollection
            ->createDocument(array('brand' => 'Nissan'))
            ->save();
        
        $wheelDocument = $wheelsCollection
            ->createDocument(array(
                'car_id' => $carDocument->getId(),
            ))
            ->save();

        $carDocument->removeRelation('wheels', $wheelDocument);
        
        $this->assertEmpty($carDocument->wheels);
    }
}