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

    /**
     * @var bool
     */
    private $isValidationEnabled = true;

    /**
     * Used for validating array of data
     * @var Document
     */
    private $validator;

    public function init()
    {
        $this->validator = $this->collection->createDocument();
    }

    /**
     * @return $this
     */
    public function enableValidation()
    {
        $this->isValidationEnabled = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableValidation()
    {
        $this->isValidationEnabled = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function isValidationEnabled()
    {
        return $this->isValidationEnabled;
    }

    /**
     * @param array $document
     * @return $this
     * @throws InvalidDocumentException
     */
    public function insert(array $document)
    {
        // validate
        if ($this->isValidationEnabled) {
            $this->validator->merge($document);
            $isValid = $this->validator->isValid();
            $this->validator->reset();
            if (!$isValid) {
                throw new InvalidDocumentException('Document is invalid on batch insert');
            }
        }

        // add to batch
        $this->add($document);
        return $this;
    }
}
