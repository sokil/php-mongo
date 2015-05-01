<?php

namespace Sokil\Mongo;

use Sokil\Mongo\Client;
use Sokil\Mongo\Collection\Definition;
use Sokil\Mongo\Document\OptimisticLockFailureException;
use Sokil\Mongo\Document\PessimisticLockFailureException;

class CollectionLockTest extends \PHPUnit_Framework_TestCase
{
    public function testLockConfiguration()
    {
        $client = new Client('mongodb://127.0.0.1');
        $client->map(array(
            'test' => array(
                'phpmongo_test_collection' => array(
                    'lock' => Definition::LOCK_OPTIMISTIC
                )
            )
        ));

        $this->assertEquals(
            Definition::LOCK_OPTIMISTIC,
            $client
                ->getDatabase('test')
                ->getCollection('phpmongo_test_collection')
                ->getOption('lock')
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Document\OptimisticLockFailureException
     */
    public function testOptimisticLock()
    {
        // init connection
        $client = new Client('mongodb://127.0.0.1');
        $client->map(array(
            'test' => array(
                'phpmongo_test_collection' => array(
                    'lock' => Definition::LOCK_OPTIMISTIC
                )
            )
        ));

        // get collection
        $collection = $client
            ->getDatabase('test')
            ->getCollection('phpmongo_test_collection');
        
        // create document
        $document = $collection
            ->createDocument(array('param' => 'value'))
            ->save();

        // check version field set
        $this->assertEquals(1, $document->get('__version__'));

        // first read of document
        $doc1 = $collection->getDocumentDirectly($document->getId());
        
        // second read of document
        $doc2 = $collection->getDocumentDirectly($document->getId());

        // update first document
        $doc1->set('param', 'valueOfDoc1')->save();
        $this->assertEquals(2, $doc1->get('__version__'));
        $this->assertEquals(1, $doc2->get('__version__'));

        // try to update second document
        $doc1->set('param', 'valueOfDoc2')->save();
    }
}