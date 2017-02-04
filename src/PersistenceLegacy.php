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

class PersistenceLegacy extends Persistence
{
    /**
     * Send data to database
     *
     * @return \Sokil\Mongo\Persistence
     */
    public function flush()
    {
        /** @var $document \Sokil\Mongo\Document */
        foreach ($this->pool as $document) {
            switch ($this->pool->offsetGet($document)) {
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
}
