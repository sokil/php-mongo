<?php

namespace Sokil\Mongo\Collection\CrudStrategy;

use Sokil\Mongo\Document;

class Common implements \Sokil\Mongo\Collection\CrudStrategy
{
    public function insert(Document $document)
    {
        $data = $document->toArray();

        // save data
        $document->getCollection()->insert($data);

        // set id
        $document->defineId($data['_id']);
    }
    
    public function update(Document $document)
    {
        $updateOperations = $document->getOperator()->toArray();

        $status = $document->getCollection()->getMongoCollection()->update(
            array('_id' => $document->getId()), $updateOperations
        );

        if ($status['ok'] != 1) {
            throw new \Sokil\Mongo\Exception(sprintf(
                'Update error: %s: %s',
                $status['err'],
                $status['errmsg']
            ));
        }

        if ($document->getOperator()->isReloadRequired()) {
            $data = $document->getCollection()->getMongoCollection()->findOne(array('_id' => $document->getId()));
            $document->replace($data);
        }

        $document->getOperator()->reset();
    }
}

