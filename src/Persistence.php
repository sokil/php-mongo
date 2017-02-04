<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo;

/**
 * Class use MongoWriteBatch classes from PECL driver above v. 1.5.0.
 * Before this version legacy persistencee must be used
 */
class Persistence implements \Countable
{
    const STATE_SAVE = 0;
    const STATE_REMOVE = 1;

    /**
     * @var \SplObjectStorage
     */
    protected $pool;

    public function __construct()
    {
        $this->pool = new \SplObjectStorage;
    }

    /**
     * Check if document already watched
     *
     * @param Document $document
     * @return bool
     */
    public function contains(Document $document)
    {
        return $this->pool->contains($document);
    }

    /**
     * Get count of documents in pool
     * @return int
     */
    public function count()
    {
        return $this->pool->count();
    }

    /**
     * Add document to watching pool for save
     *
     * @param Document $document
     * @return \Sokil\Mongo\Persistence
     */
    public function persist(Document $document)
    {
        $this->pool->attach($document, self::STATE_SAVE);

        return $this;
    }

    /**
     * Add document to watching pool for remove
     *
     * @param Document $document
     * @return \Sokil\Mongo\Persistence
     */
    public function remove(Document $document)
    {
        $this->pool->attach($document, self::STATE_REMOVE);

        return $this;
    }

    /**
     * Send data to database
     *
     * @return \Sokil\Mongo\Persistence
     */
    public function flush()
    {
        $insert = array();
        $update = array();
        $delete = array();

        // fill batch objects
        foreach ($this->pool as $document) {
            /* @var $document \Sokil\Mongo\Document */

            // collection
            $collection = $document->getCollection();
            $collectionName = $collection->getName();

            // persisting
            switch ($this->pool->offsetGet($document)) {
                case self::STATE_SAVE:
                    if ($document->isStored()) {
                        if (!isset($update[$collectionName])) {
                            $update[$collectionName] = new \MongoUpdateBatch($collection->getMongoCollection());
                        }
                        $update[$collectionName]->add(array(
                            'q' => array(
                                '_id' => $document->getId(),
                            ),
                            'u' => $document->getOperator()->toArray(),
                        ));
                    } else {
                        if (!isset($insert[$collectionName])) {
                            $insert[$collectionName] = new \MongoInsertBatch($collection->getMongoCollection());
                        }
                        $insert[$collectionName]->add($document->toArray());
                    }
                    break;

                case self::STATE_REMOVE:
                    // delete document form db
                    if ($document->isStored()) {
                        if (!isset($delete[$collectionName])) {
                            $delete[$collectionName] = new \MongoDeleteBatch($collection->getMongoCollection());
                        }
                        $delete[$collectionName]->add(array(
                            'q' => array(
                                '_id' => $document->getId(),
                            ),
                            'limit' => 1,
                        ));
                    }
                    // remove link form pool
                    $this->detach($document);
                    break;
            }
        }

        // write operations
        $writeOptions = array('w' => 1);
        
        // execute batch insert operations
        if ($insert) {
            foreach ($insert as $collectionName => $collectionInsert) {
                $collectionInsert->execute($writeOptions);
            }
        }

        // execute batch update operations
        if ($update) {
            foreach ($update as $collectionName => $collectionUpdate) {
                $collectionUpdate->execute($writeOptions);
            }
        }

        // execute batch delete operations
        if ($delete) {
            foreach ($delete as $collectionName => $collectionDelete) {
                $collectionDelete->execute($writeOptions);
            }
        }

        return $this;
    }

    /**
     * Stop watching document
     *
     * @param Document $document
     * @return \Sokil\Mongo\Persistence
     */
    public function detach(Document $document)
    {
        $this->pool->detach($document);

        return $this;
    }

    /**
     * Detach all documents from pool
     * @return \Sokil\Mongo\Persistence
     */
    public function clear()
    {
        $this->pool->removeAll($this->pool);

        return $this;
    }
}
