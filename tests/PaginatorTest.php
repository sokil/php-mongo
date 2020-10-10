<?php

namespace Sokil\Mongo;

use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;
    
    public function setUp(): void
    {
        // connect to mongo
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);
        
        // select database
        $database = $client->getDatabase('test');
        
        // select collection
        $this->collection = $database->getCollection('phpmongo_test_collection');
        $this->collection->delete();
    }
    
    public function tearDown(): void
    {
        $this->collection->delete();
    }
    
    public function testPaginatorWhenPageExistsAndRowsGreaterThanItemsOnPageRequested()
    {        
        $d11 = $this->collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = $this->collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = $this->collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = $this->collection->createDocument(array('param1' => 2, 'param2' => 2))->save();
        
        $pager = $this->collection
            ->find()
            ->paginate(2, 2);
        

        $this->assertEquals(4, $pager->getTotalRowsCount());
        $this->assertEquals(2, $pager->getTotalPagesCount());
        $this->assertEquals(2, $pager->getCurrentPage());

        $pager->rewind();

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
        $d11 = $this->collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = $this->collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = $this->collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = $this->collection->createDocument(array('param1' => 2, 'param2' => 2))->save();

        $cursor = $this->collection->find();

        $pager = new Paginator;
        $pager
            ->setItemsOnPage(1)
            ->setCurrentPage(2)
            ->setCursor($cursor);

        $pager->rewind();

        $this->assertEquals($d12->getId(), $pager->key());
    }
    
    public function testPaginatorWhenPageExistsAndRowsLessThenItemsOnPage()
    {        
        $d11 = $this->collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = $this->collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = $this->collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = $this->collection->createDocument(array('param1' => 2, 'param2' => 2))->save();
        
        $pager = $this->collection
            ->find()
            ->paginate(1, 20);
        
        $this->assertEquals(4, $pager->getTotalRowsCount());
        
        $this->assertEquals(1, $pager->getTotalPagesCount());
        
        $this->assertEquals(1, $pager->getCurrentPage());

        $pager->rewind();

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
        $d11 = $this->collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = $this->collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = $this->collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = $this->collection->createDocument(array('param1' => 2, 'param2' => 2))->save();
        
        $pager = $this->collection
            ->find()
            ->paginate(20, 2);
        
        $this->assertEquals(4, $pager->getTotalRowsCount());
        
        $this->assertEquals(2, $pager->getTotalPagesCount());
        
        $this->assertEquals(2, $pager->getCurrentPage());

        $pager->rewind();

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
        $pager = $this->collection
            ->find()
            ->paginate(10, 20);

        $pager->rewind();

        $this->assertNull($pager->current());
    }

    public function testPaginatorIteratorInterface()
    {
        $d11 = $this->collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = $this->collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = $this->collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = $this->collection->createDocument(array('param1' => 2, 'param2' => 2))->save();

        $pager = $this->collection
            ->find()
            ->paginate(20, 2);

        $pager->rewind();

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