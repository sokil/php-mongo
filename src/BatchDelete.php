<?php

namespace Sokil\Mongo;

class BatchDelete extends BatchOperation
{
    protected $batchClass = '\MongoDeleteBatch';

    /**
     * @param $expression
     * @param bool|false $justOne
     * @return $this
     * @throws Exception
     */
    public function delete($expression, $justOne = false)
    {
        $this->add(array(
            'q'         => Expression::convertToArray($expression),
            'limit'     => $justOne ? 1: 0,
        ));
        return $this;
    }
}
