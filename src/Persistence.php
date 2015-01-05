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

class Persistence implements \Countable
{
    const STATE_SAVE = 0;
    const STATE_REMOVE = 1;

    /**
     * @var \SplObjectStorage
     */
    private $_pool;

    public function __construct()
    {
        $this->_pool = new \SplObjectStorage;
    }

    /**
     * Check if document already watched
     *
     * @param Document $document
     * @return bool
     */
    public function contains(Document $document)
    {
        return $this->_pool->contains($document);
    }

    /**
     * Get count of documents in pool
     * @return int
     */
    public function count()
    {
        return $this->_pool->count();
    }

    /**
     * Add document to watching pool for save
     *
     * @param Document $document
     * @return \Sokil\Mongo\Persistence
     */
    public function persist(Document $document)
    {
        $this->_pool->attach($document, self::STATE_SAVE);

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
        $this->_pool->attach($document, self::STATE_REMOVE);

        return $this;
    }

    /**
     * Send data to database
     *
     * @return \Sokil\Mongo\Persistence
     */
    public function flush()
    {
        /** @var $document \Sokil\Mongo\Document */
        foreach($this->_pool as $document) {
            switch($this->_pool->offsetGet($document)) {
                case self::STATE_SAVE:
                    $document->save();
                    break;

                case self::STATE_REMOVE:
                    // delete document form db
                    $document->delete();
                    // remove link form pool
                    $this->detach($document);
                    break;
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
        $this->_pool->detach($document);

        return $this;
    }

    /**
     * Detach all documents from pool
     * @return \Sokil\Mongo\Persistence
     */
    public function clear()
    {
        $this->_pool->removeAll($this->_pool);

        return $this;
    }
}