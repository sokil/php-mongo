<?php

namespace Sokil\Mongo;

class Queue implements \Countable
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $_collection;
    
    public function __construct(Database $database, $channel)
    {
        $this->_collection = $database
            ->getCollection($channel)
            ->disableDocumentPool();
    }
    
    /**
     * Add item to queue
     * 
     * @param mixed $payload data to send
     * @param int $priority more priority num give quicker getting from queue
     * @return \Sokil\Mongo\Queue
     */
    public function enqueue($payload, $priority = 0)
    {
        $this->_collection
            ->createDocument(array(
                'payload'   => $payload,
                'priority'  => (int) $priority,
                'datetime'  => new \MongoDate,
            ))
            ->save();
        
        return $this;
    }
    
    /**
     * Get item from queue as is
     * 
     * @return mixed
     */
    public function dequeuePlain()
    {
        $document = $this->_collection
            ->find()
            ->sort(array(
                'priority' => -1,
                'datetime' => 1,
            ))
            ->findAndRemove();
        
        if(!$document) {
            return null;
        }
        
        return $document->get('payload');
    }
    
    /**
     * Get item from queue as Structure if array put into queue
     * 
     * @return mixed|\Sokil\Mongo\Structure
     */
    public function dequeue()
    {
        $value = $this->dequeuePlain();
        if(!is_array($value)) {
            return $value;
        }

        $structure = new Structure();
        $structure->mergeUnmodified($value);

        return $structure;
    }
    
    /**
     * Get number of elements in queue
     * 
     * @return int
     */
    public function count()
    {
        return count($this->_collection);
    }
    
    /**
     * Clear queue
     * 
     * @return \Sokil\Mongo\Queue
     */
    public function clear()
    {
        $this->_collection->delete();
        return $this;
    }
}