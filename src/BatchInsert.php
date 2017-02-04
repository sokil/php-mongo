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

use Sokil\Mongo\Document\InvalidDocumentException;

class BatchInsert extends BatchOperation
{
    protected $batchClass = '\MongoInsertBatch';

    private $isValidationEnabled = true;

    /**
     * Used for validating array of data
     * @var Document
     */
    private static $validator;

    public function __construct(Collection $collection, $writeConcern = null, $timeout = null, $ordered = null)
    {
        parent::__construct($collection, $writeConcern, $timeout, $ordered);

        self::$validator = $collection->createDocument();
    }

    public function enableValidation()
    {
        $this->isValidationEnabled = true;
        return $this;
    }

    public function disableValidation()
    {
        $this->isValidationEnabled = false;
        return $this;
    }

    public function isValidationEbabled()
    {
        return $this->isValidationEnabled;
    }

    public function insert(array $document)
    {
        if (!$this->isValidationEnabled) {
            self::$validator->merge($document);
            $isValid = self::$validator->isValid();
            self::$validator->reset();

            if (!$isValid) {
                throw new InvalidDocumentException('Document is invalid on batch insert');
            }
        }

        $this->add($document);
        return $this;
    }
}
