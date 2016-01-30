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

class BatchUpdate extends BatchOperation
{
    protected $batchClass = '\MongoUpdateBatch';

    /**
     * @param Expression|array|callable $expression expression to define
     * @param Operator|array|callable $data new data or operators to update
     * @param bool|false $multiple
     * @param bool|false $upsert
     * @return $this
     */
    public function update(
        $expression,
        $data,
        $multiple = false,
        $upsert = false
    ) {
        $this->add(array(
            'q'         => Expression::convertToArray($expression),
            'u'         => Operator::convertToArray($data),
            'multiple'  => $multiple,
            'upsert'    => $upsert,
        ));

        return $this;
    }
}
