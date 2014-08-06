<?php

namespace Sokil\Mongo;

class PaginatorTest extends \PHPUnit_Framework_testCase
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
    
    public function testPaginatorWhenPageExistsAndRowsGreaterThanItemsOnPageRequested()
    {
        self::$collection->delete();
        
        $d11 = self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = self::$collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = self::$collection->createDocument(array('param1' => 2, 'param2' => 2))->save();
        
        $pager = self::$collection
            ->find()
            ->paginate(2, 2);
        
        $this->assertEquals(4, $pager->getTotalRowsCount());
        
        $this->assertEquals(2, $pager->getTotalPagesCount());
        
        $this->assertEquals(2, $pager->getCurrentPage());
        
        $this->assertEquals(
            $d21->getId(), 
            $pager->current()->getId()
        );
        
        $pager->next();
        $this->assertEquals(
            $d22->getId(), 
            $pager->current()->getId()
        );
    }
    
    public function testPaginatorWhenPageExistsAndRowsLessThenItemsOnPage()
    {
        self::$collection->delete();
        
        $d11 = self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = self::$collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = self::$collection->createDocument(array('param1' => 2, 'param2' => 2))->save();
        
        $pager = self::$collection
            ->find()
            ->paginate(1, 20);
        
        $this->assertEquals(4, $pager->getTotalRowsCount());
        
        $this->assertEquals(1, $pager->getTotalPagesCount());
        
        $this->assertEquals(1, $pager->getCurrentPage());
        
        $this->assertEquals(
            $d11->getId(), 
            $pager->current()->getId()
        );
        
        $pager->next();
        $this->assertEquals(
            $d12->getId(), 
            $pager->current()->getId()
        );
    }
    
    public function testPaginatorWhenPageNotExistsRequested()
    {
        self::$collection->delete();
        
        $d11 = self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = self::$collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = self::$collection->createDocument(array('param1' => 2, 'param2' => 2))->save();
        
        $pager = self::$collection
            ->find()
            ->paginate(20, 2);
        
        $this->assertEquals(4, $pager->getTotalRowsCount());
        
        $this->assertEquals(2, $pager->getTotalPagesCount());
        
        $this->assertEquals(2, $pager->getCurrentPage());
        
        $this->assertEquals(
            $d21->getId(), 
            $pager->current()->getId()
        );
        
        $pager->next();
        $this->assertEquals(
            $d22->getId(), 
            $pager->current()->getId()
        );
    }
    
    public function testPaginatorOverEmptyList()
    {
        self::$collection->delete();
        
        $pager = self::$collection
            ->find()
            ->paginate(10, 20);
        
        $this->assertNull($pager->current());
    }
}