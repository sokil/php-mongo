<?php

namespace Sokil\Mongo\Collection\CrudStrategy;

class Common extends \Sokil\Mongo\Collection\CrudStrategy
{
    public function findOne(array $query)
    {
        return $this->collection->findOne($query);
    }
    
    public function insert(array $document)
    {
        $this->collection->insert($document);
        return $document['_id'];
    }
    
    public function update(array $query, array $operations)
    {
        $status = $this->collection->update(
            $query,
            $operations
        );

        if ($status['ok'] != 1) {
            throw new \Sokil\Mongo\Exception(sprintf(
                'Update error: %s: %s',
                $status['err'],
                $status['errmsg']
            ));
        }
    }

    public function delete(array $query)
    {
        $status = $this->collection->remove($query);

        if(true !== $status && $status['ok'] != 1) {
            throw new \Sokil\Mongo\Exception(sprintf('Delete document error: %s', $status['err']));
        }
    }
}

