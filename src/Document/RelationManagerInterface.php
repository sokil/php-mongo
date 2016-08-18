<?php

namespace Sokil\Mongo\Document;


use Sokil\Mongo\Document;

interface RelationManagerInterface
{

    /**
     * Check if relation with specified name configured
     * @param $name
     * @return bool
     */
    public function isRelationExists($name);

    /**
     * Get related documents
     * @param $relationName
     * @return array|null|Document
     * @throws \Sokil\Mongo\Exception
     */
    public function getRelated($relationName);

    /**
     * @param $relationName
     * @param Document $document
     * @return $this
     * @throws OptimisticLockFailureException
     * @throws \Exception
     * @throws \Sokil\Mongo\Exception
     */
    public function addRelation($relationName, Document $document);

    /**
     * @param $relationName
     * @param Document $document
     * @return $this
     * @throws OptimisticLockFailureException
     * @throws \Exception
     * @throws \Sokil\Mongo\Exception
     */
    public function removeRelation($relationName, Document $document = null);
}