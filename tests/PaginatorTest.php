<?php

namespace Sokil\Mongo;

class PaginatorTest extends \PHPUnit_Framework_TestCase
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

    public function testSetCursor()
    {
        self::$collection->delete();

        $d11 = self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = self::$collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = self::$collection->createDocument(array('param1' => 2, 'param2' => 2))->save();

        $cursor = self::$collection->find();

        $pager = new Paginator;
        $pager
            ->setItemsOnPage(1)
            ->setCurrentPage(2)
            ->setCursor($cursor);

        $this->assertEquals($d12->getId(), $pager->key());
    }

    public function testSetQueryBuilder()
    {
        self::$collection->delete();

        $d11 = self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = self::$collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = self::$collection->createDocument(array('param1' => 2, 'param2' => 2))->save();

        $cursor = self::$collection->find();

        $pager = new Paginator;
        $pager
            ->setItemsOnPage(1)
            ->setCurrentPage(2)
            ->setQueryBuilder($cursor);

        $this->assertEquals($d12->getId(), $pager->key());
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

    public function testPaginatorIteratorInterface()
    {
        self::$collection->delete();

        $d11 = self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = self::$collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = self::$collection->createDocument(array('param1' => 2, 'param2' => 2))->save();

        $pager = self::$collection
            ->find()
            ->paginate(20, 2);

        $this->assertEquals($d21->getId(), $pager->current()->getId());
        $this->assertEquals((string) $d21->getId(), $pager->key());
        $this->assertTrue($pager->valid());

        $pager->next();

        $this->assertEquals($d22->getId(), $pager->current()->getId());
        $this->assertEquals((string) $d22->getId(), $pager->key());
        $this->assertTrue($pager->valid());

        $pager->next();
        $this->assertFalse($pager->valid());

        $pager->rewind();

        $this->assertEquals($d21->getId(), $pager->current()->getId());
        $this->assertEquals((string) $d21->getId(), $pager->key());
    }
}