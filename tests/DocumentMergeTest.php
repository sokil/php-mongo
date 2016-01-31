<?php

namespace Sokil\Mongo;

class UserDocumentMock extends \Sokil\Mongo\Document
{
    protected $schema = array(
        // simple value
        'status' => 'ACTIVE',
        // list value, default value id simple
        'comments' => 'none',
        // list value, default value id list
        'languages' => array('php', 'js', 'css', 'html', 'sql'),
        // embedded document
        'profile' => array(
            // simple value of embedded document
            'name' => 'USER_NAME',
            // embedded document of embedded document
            'birth' => array(
                'year' => 1984,
                'month' => 8,
                'day' => 10,
            ),
            // list of embedded document, default value is simple
            'interests' => 'none',
            // list of embedded document, default value is list
            'roles' => array('writer', 'reader', 'watcher'),
        ),
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
    
    public function testSetUnmodifiedData_mergeListToSimple()
    {
        $mongoId = new \MongoId;
        
        $user = new UserDocumentMock(
            $this->collection, 
            array(
                '_id' => $mongoId,
                'comments' => array(
                    'good luck!',
                    'wish you luck!'
                ),
            ),
            array(
                'stored' => true
            )
        );
        
        $this->assertEquals(array(
            '_id' => $mongoId,
            'comments' => array(
                'good luck!',
                'wish you luck!'
            )
        ), $user->toArray());
    }
    
    public function testSetUnmodifiedData_embeddedDocument_mergeListToSimple()
    {
        $mongoId = new \MongoId;
        
        $user = new UserDocumentMock(
            $this->collection, 
            array(
                '_id' => $mongoId,
                'profile' => array(
                    'interests' => array('snowboarding', 'programming', 'traveling')
                ),
            ),
            array(
                'stored' => true
            )
        );
        
        $this->assertEquals(array(
            '_id' => $mongoId,
            'profile' => array(
                'interests' => array('snowboarding', 'programming', 'traveling')
            ),
        ), $user->toArray());
    }
    
    public function testSetModifiedData_mergeListToSimple()
    {
        $user = new UserDocumentMock(
            $this->collection, 
            array(
                'profile' => array(
                    'interests' => array('snowboarding', 'programming', 'traveling')
                ),
            )
        );
        
        $this->assertEquals(array(
            'status' => 'ACTIVE',
            'comments' => 'none',
            'languages' => array('php', 'js', 'css', 'html', 'sql'),
            'profile' => array(
                'name' => 'USER_NAME',
                'birth' => array(
                    'year' => 1984,
                    'month' => 8,
                    'day' => 10,
                ),
                'interests' => array('snowboarding', 'programming', 'traveling'),
                'roles' => array('writer', 'reader', 'watcher'),
            ),
        ), $user->toArray());
    }
    
    public function testSetModifiedData_mergeListToList()
    {
        $user = new UserDocumentMock(
            $this->collection, 
            array(
                'languages' => array('python', 'java'),
            )
        );
        
        $this->assertEquals(array(
            'status' => 'ACTIVE',
            'comments' => 'none',
            'languages' => array('python', 'java'),
            'profile' => array(
                'name' => 'USER_NAME',
                'birth' => array(
                    'year' => 1984,
                    'month' => 8,
                    'day' => 10,
                ),
                'interests' => 'none',
                'roles' => array('writer', 'reader', 'watcher'),
            ),
        ), $user->toArray());
    }
}