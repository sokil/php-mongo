PHPMongo
========

Active Record implementation of Mongo adapter on PHP

Example:
         
    /**
     * Connect to collection
     */

    // connect to mongo
    $client = new Client('mongodb://127.0.0.1');

    // select database
    $database = $client->getDatabase('test');

    // select collection
    $collection = $database->getCollection('phpmongo_test_collection');

    /**
     * Create document
     */

    $document = $collection->create(array(
        'l1'   => array(
            'l11'   => 'l11value',
            'l12'   => 'l12value',
        ),
        'l2'   => array(
            'l21'   => 'l21value',
            'l22'   => 'l22value',
        ),
    ));

    $collection->save($document);

    /**
     * Update document
     */

    $document->set('l1.l12', 'updated');
    $collection->save($document);

    /**
     * Read document
     */

    $document = $collection->findById($documentId);

    /**
     * Delete document
     */

    $collection->delete($document);
