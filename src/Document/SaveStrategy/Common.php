<?php

namespace Sokil\Mongo\Document\SaveStrategy;

class Common extends \Sokil\Mongo\Document\SaveStrategy
{
    protected function insert()
    {
        $updateOperations = $this->document->getOperator()->toArray();

        $status = $this->document->getCollection()->getMongoCollection()->update(
            array('_id' => $this->document->getId()), $updateOperations
        );

        if ($status['ok'] != 1) {
            throw new \Sokil\Mongo\Exception(sprintf(
                'Update error: %s: %s',
                $status['err'],
                $status['errmsg']
            ));
        }

        if ($this->document->getOperator()->isReloadRequired()) {
            $data = $this->document->getCollection()->getMongoCollection()->findOne(array('_id' => $this->document->getId()));
            $this->document->merge($data);
        }

        $this->document->getOperator()->reset();
    }
    
    protected function update()
    {
        $data = $this->document->toArray();

        // save data
        $this->document->getCollection()->insert($data);

        // set id
        $this->document->defineId($data['_id']);
    }
}

