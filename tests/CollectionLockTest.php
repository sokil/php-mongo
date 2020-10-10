<?php

namespace Sokil\Mongo;

use Sokil\Mongo\Client;
use Sokil\Mongo\Collection\Definition;
use Sokil\Mongo\Document\OptimisticLockFailureException;
use Sokil\Mongo\Document\PessimisticLockFailureException;
use PHPUnit\Framework\TestCase;

class CollectionLockTest extends TestCase
{
    public function testLockConfiguration()
    {
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);
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

    public function testOptimisticLock()
    {
        $this->expectException(\Sokil\Mongo\Document\OptimisticLockFailureException::class);

        // init connection
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);
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
        $this->assertEquals(null, $document->get('__version__'));

        // first read of document
        $doc1 = $collection->getDocumentDirectly($document->getId());
        
        // second read of document
        $doc2 = $collection->getDocumentDirectly($document->getId());

        // update first document
        $doc1->set('param', 'valueOfDoc1')->save();
        $this->assertEquals(1, $doc1->get('__version__'));
        $this->assertEquals(null, $doc2->get('__version__'));

        // try to update second document
        $doc2->set('param', 'valueOfDoc2')->save();
    }
}