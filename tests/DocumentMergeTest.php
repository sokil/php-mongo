<?php

namespace Sokil\Mongo;

class DocumentMock extends \Sokil\Mongo\Document
{
    protected $_data = array(
        'status' => 'ACTIVE',
        'profile' => array(
            'name' => 'USER_NAME',
            'birth' => array(
                'year' => 1984,
                'month' => 8,
                'day' => 10,
            )
        ),
        'interests' => 'none',
        'languages' => ['php'],
    );
}

class DocumentMergeTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;
    
    public function setUp() 
    {
        $client = new Client();
        $database = $client->getDatabase('test');
        $this->collection = $database
            ->getCollection('phpmongo_test_collection')
            ->delete();
    }
    
    public function tearDown()
    {
        $this->collection->delete();
    }
    
    public function testSetUnmodifiedData()
    {
        $document = new DocumentMock(
            $this->collection, 
            array(
                '_id' => new \MongoId,
                'profile' => array(
                    'name' => 'dsokil'
                ),
                'interests' => array('snowboarding', 'programming', 'traveling')
            ),
            array(
                'stored' => true
            )
        );
    }
    
    public function testSetModifiedData()
    {
        $document = new DocumentMock(
            $this->collection, 
            array(
                'profile' => array(
                    'name' => 'dsokil',
                    'birth' => array(
                        'month' => 9
                    ),
                ),
                'interests' => array('snowboarding', 'programming', 'traveling'),
                'languages' => array('python', 'java'),
            )
        );
        
        $this->assertEquals(array(
            'status' => 'ACTIVE',
            'profile' => array(
                'name' => 'dsokil',
                'birth' => array(
                    'year' => 1984,
                    'month' => 9,
                    'day' => 10,
                )
            ),
            'interests' => array('snowboarding', 'programming', 'traveling'),
            'languages' => array('python', 'java'),
        ), $document->toArray());
    }
}