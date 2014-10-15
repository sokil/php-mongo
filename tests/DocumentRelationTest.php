<?php

namespace Sokil\Mongo;

class DocumentRelationTest extends \PHPUnit_Framework_TestCase
{
    private $database;
    
    public function setUp()
    {
        // connect to mongo
        $client = new Client(MONGO_DSN);
        
        // select database
        $this->database = $client
            ->map(array(
                'test'  => array(
                    'cars'      => '\Sokil\Mongo\DocumentRelationTest\CarsCollection',
                    'engines'   => '\Sokil\Mongo\DocumentRelationTest\EnginesCollection',
                    'wheels'    => '\Sokil\Mongo\DocumentRelationTest\WheelsCollection',
                    'drivers'   => '\Sokil\Mongo\DocumentRelationTest\DriversCollection',
                ),
            ))
            ->getDatabase('test');
    }
    
    public function tearDown()
    {
        $this->database->getCollection('cars')->delete();
        $this->database->getCollection('engines')->delete();
        $this->database->getCollection('drivers')->delete();
        $this->database->getCollection('wheels')->delete();
    }
    
    /**
     * A -> HAS_ONE -> B
     */
    public function testGetRelated_HasOne()
    {
        // collections
        $carsCollection = $this->database->getCollection('cars');
        $enginesCollection = $this->database->getCollection('engines');
        
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
    public function testGetRelated_Belongs()
    {
        // collections
        $carsCollection = $this->database->getCollection('cars');
        $enginesCollection = $this->database->getCollection('engines');
        
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
    public function testGetRelated_HasMany()
    {
        // collections
        $carsCollection = $this->database->getCollection('cars');
        $wheelsCollection = $this->database->getCollection('wheels');
        
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
    
    public function testGetRelated_ManyMany_RequestFromCollectionWithLocalyStoredRelationData()
    {
        $carsCollection = $this->database->getCollection('cars');
        $driversCollection = $this->database->getCollection('drivers');
        
        $driver1 = $driversCollection->createDocument(array('name' => 'Dmytro'))->save();
        $driver2 = $driversCollection->createDocument(array('name' => 'Natalia'))->save();
        
        $car1 = $carsCollection->createDocument(array(
            'number' => 'AA0123AK',
            'driver_id' => array(
                $driver1->getId(),
                $driver2->getId(),
            ),
        ))->save();
        
        $car2 = $carsCollection->createDocument(array(
            'number' => 'AA4567AK',
            'driver_id' => array(
                $driver1->getId(),
                $driver2->getId(),
            ),
        ))->save();
        
        // check emdedded relation fields
        $this->assertEquals(array(
            $driver1,
            $driver2,
        ), array_values($car1->getRelated('drivers')));
        
        $this->assertEquals(array(
            $driver1,
            $driver2,
        ), array_values($car2->getRelated('drivers')));
    }
    
    public function testGetRelated_ManyMany_RequestFromCollectionWithoutLocalyStoredRelationData()
    {
        $carsCollection = $this->database->getCollection('cars');
        $driversCollection = $this->database->getCollection('drivers');
        
        $driver1 = $driversCollection->createDocument(array('name' => 'Dmytro'))->save();
        $driver2 = $driversCollection->createDocument(array('name' => 'Natalia'))->save();
        
        $car1 = $carsCollection->createDocument(array(
            'number' => 'AA0123AK',
            'driver_id' => array(
                $driver1->getId(),
                $driver2->getId(),
            ),
        ))->save();
        
        $car2 = $carsCollection->createDocument(array(
            'number' => 'AA4567AK',
            'driver_id' => array(
                $driver1->getId(),
                $driver2->getId(),
            ),
        ))->save();
        
        $this->assertEquals(array(
            $car1,
            $car2,
        ), array_values($driver1->getRelated('cars')));
        
        $this->assertEquals(array(
            $car1,
            $car2,
        ), array_values($driver2->getRelated('cars')));
    }
    
    public function testGetRelated_Belongs_Cache()
    {
        // collections
        $carsCollection = $this->database->getCollection('cars');
        $wheelsCollection = $this->database->getCollection('wheels');
        
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
        $carsCollection = $this->database->getCollection('cars');
        $wheelsCollection = $this->database->getCollection('wheels');
        
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
        $carsCollection = $this->database->getCollection('cars');
        $enginesCollection = $this->database->getCollection('engines');
        
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
        $carsCollection = $this->database->getCollection('cars');
        $wheelsCollection = $this->database->getCollection('wheels');
        
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
    
    public function testAddRelation_ManyMany()
    {
        $carsCollection = $this->database->getCollection('cars');
        $driversCollection = $this->database->getCollection('drivers');
        
        $driver1 = $driversCollection->createDocument(array('name' => 'Dmytro'))->save();
        $driver2 = $driversCollection->createDocument(array('name' => 'Natalia'))->save();
        
        $car1 = $carsCollection
            ->createDocument(array(
                'number' => 'AA0123AK',
            ))
            ->save();
        
        $car2 = $carsCollection
            ->createDocument(array(
                'number' => 'AA4567AK',
            ))
            ->save();
        
        $car1->addRelation('drivers', $driver1);
        $driver2->addRelation('cars', $car1);
        
        $car2->addRelation('drivers', $driver1);
        $driver2->addRelation('cars', $car2);
        
        // check emdedded relation fields
        $this->assertEquals(array(
            $driver1,
            $driver2,
        ), array_values($car1->getRelated('drivers')));
        
        $this->assertEquals(array(
            $driver1,
            $driver2,
        ), array_values($car2->getRelated('drivers')));
    }
    
    public function testRemoveRelation_Belongs()
    {
        // collections
        $carsCollection = $this->database->getCollection('cars');
        $wheelsCollection = $this->database->getCollection('wheels');
        
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
        $carsCollection = $this->database->getCollection('cars');
        $enginesCollection = $this->database->getCollection('engines');
        
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
        $carsCollection = $this->database->getCollection('cars');
        $wheelsCollection = $this->database->getCollection('wheels');
        
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
    
    public function testRemoveRelation_ManyMany()
    {
        $carsCollection = $this->database->getCollection('cars');
        $driversCollection = $this->database->getCollection('drivers');
        
        $driver1 = $driversCollection->createDocument(array('name' => 'Dmytro'))->save();
        $driver2 = $driversCollection->createDocument(array('name' => 'Natalia'))->save();
        
        $car1 = $carsCollection
            ->createDocument(array(
                'number' => 'AA0123AK',
                'driver_id' => array(
                    $driver1->getId(),
                    $driver2->getId(),
                )
            ))
            ->save();
        
        $car2 = $carsCollection
            ->createDocument(array(
                'number' => 'AA4567AK',
                'driver_id' => array(
                    $driver1->getId(),
                    $driver2->getId(),
                )
            ))
            ->save();
        
        $car1->removeRelation('drivers', $driver1);
        $driver2->removeRelation('cars', $car2);
        
        // check emdedded relation fields
        $this->assertEquals(array(
            $driver2,
        ), array_values($car1->getRelated('drivers')));
        
        $this->assertEquals(array(
            $driver1,
        ), array_values($car2->getRelated('drivers')));
    }
}