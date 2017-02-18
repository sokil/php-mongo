<?php

namespace Sokil\Mongo\Document;

use Sokil\Mongo\Document;

class RelationManager
{
    private $relations;

    /**
     *
     * @var \Sokil\Mongo\Document
     */
    private $document;

    private $resolvedRelationIds = array();
    
    public function __construct(Document $document = null)
    {
        $this->document = $document;
        $this->relations = $document->getRelationDefinition();
    }

    /**
     * Check if relation with specified name configured
     * @param string $name
     * @return boolean
     */
    public function isRelationExists($name)
    {
        return isset($this->relations[$name]);
    }

    /**
     * Get related documents
     * @param string $relationName name of relation
     */
    public function getRelated($relationName)
    {
        // check if relation exists
        if (!$this->isRelationExists($relationName)) {
            throw new \Sokil\Mongo\Exception('Relation with name "' . $relationName . '" not found');
        }

        // get relation metadata
        $relation = $this->relations[$relationName];

        $relationType = $relation[0];
        $targetCollectionName = $relation[1];

        // get target collection
        $foreignCollection = $this->document
            ->getCollection()
            ->getDatabase()
            ->getCollection($targetCollectionName);

        // check if relation already resolved
        if (isset($this->resolvedRelationIds[$relationName])) {
            if (is_array($this->resolvedRelationIds[$relationName])) {
                // has_many, many_many
                return $foreignCollection->getDocuments($this->resolvedRelationIds[$relationName]);
            } else {
                //has_one, belongs
                return $foreignCollection->getDocument($this->resolvedRelationIds[$relationName]);
            }
        }

        switch ($relationType) {
            case Document::RELATION_HAS_ONE:
                $localKey = isset($relation['localKey']) ? $relation['localKey'] : '_id';
                $foreignKey = $relation[2];

                $document = $foreignCollection
                    ->find()
                    ->where($foreignKey, $this->document->get($localKey))
                    ->findOne();

                if ($document) {
                    $this->resolvedRelationIds[$relationName] = (string) $document->getId();
                }

                return $document;

            case Document::RELATION_BELONGS:
                $localKey = $relation[2];
                $foreignKey = isset($relation['foreignKey']) ? $relation['foreignKey'] : '_id';

                if ($foreignKey === '_id') {
                    $document = $foreignCollection->getDocument($this->document->get($localKey));
                } else {
                    $document = $foreignCollection
                        ->find()
                        ->where($foreignKey, $this->document->get($localKey))
                        ->findOne();
                }

                if ($document) {
                    $this->resolvedRelationIds[$relationName] = (string) $document->getId();
                }

                return $document;

            case Document::RELATION_HAS_MANY:
                $localKey = isset($relation['localKey']) ? $relation['localKey'] : '_id';
                $foreignKey = $relation[2];

                $documents = $foreignCollection
                    ->find()
                    ->where($foreignKey, $this->document->get($localKey))
                    ->findAll();

                foreach ($documents as $document) {
                    $this->resolvedRelationIds[$relationName][] = (string) $document->getId();
                }

                return $documents;

            case Document::RELATION_MANY_MANY:
                $isRelationListStoredInternally = isset($relation[3]) && $relation[3];
                if ($isRelationListStoredInternally) {
                    // relation list stored in this document
                    $localKey = $relation[2];
                    $foreignKey = isset($relation['foreignKey']) ? $relation['foreignKey'] : '_id';
                    ;

                    $relatedIdList = $this->document->get($localKey);
                    if (!$relatedIdList) {
                        return array();
                    }

                    $documents = $foreignCollection
                        ->find()
                        ->whereIn($foreignKey, $relatedIdList)
                        ->findAll();
                } else {
                    // relation list stored in external document
                    $localKey = isset($relation['localKey']) ? $relation['localKey'] : '_id';
                    ;
                    $foreignKey = $relation[2];

                    $documents = $foreignCollection
                        ->find()
                        ->where($foreignKey, $this->document->get($localKey))
                        ->findAll();
                }

                foreach ($documents as $document) {
                    $this->resolvedRelationIds[$relationName][] = (string) $document->getId();
                }

                return $documents;
                
            default:
                throw new \Sokil\Mongo\Exception(
                    'Unsupported relation type "' . $relationType . '" when resolve relation "' . $relationName . '"'
                );
        }
    }

    public function addRelation($relationName, Document $document)
    {
        if (!$this->isRelationExists($relationName)) {
            throw new \Exception('Relation "' . $relationName . '" not configured');
        }

        $relation = $this->relations[$relationName];

        list($relationType, $relatedCollectionName, $field) = $relation;

        $relatedCollection = $this->document
            ->getCollection()
            ->getDatabase()
            ->getCollection($relatedCollectionName);

        if (!$relatedCollection->hasDocument($document)) {
            throw new \Sokil\Mongo\Exception('Document must belongs to related collection');
        }

        switch ($relationType) {
            case Document::RELATION_BELONGS:
                if (!$document->isStored()) {
                    throw new \Sokil\Mongo\Exception(sprintf(
                        'Document %s must be saved before adding relation',
                        get_class($document)
                    ));
                }
                $this->document->set($field, $document->getId());
                break;

            case Document::RELATION_HAS_ONE:
                if (!$this->document->isStored()) {
                    throw new \Sokil\Mongo\Exception(
                        'Document ' . get_class($this) . ' must be saved before adding relation'
                    );
                }
                $document->set($field, $this->document->getId())->save();
                break;

            case Document::RELATION_HAS_MANY:
                if (!$this->document->isStored()) {
                    throw new \Sokil\Mongo\Exception(
                        'Document ' . get_class($this) . ' must be saved before adding relation'
                    );
                }
                $document->set($field, $this->document->getId())->save();
                break;

            case Document::RELATION_MANY_MANY:
                $isRelationListStoredInternally = isset($relation[3]) && $relation[3];
                if ($isRelationListStoredInternally) {
                    $this->document->push($field, $document->getId())->save();
                } else {
                    $document->push($field, $this->document->getId())->save();
                }
                break;

            default:
                throw new \Sokil\Mongo\Exception(
                    'Unsupported relation type "' . $relationType . '" when resolve relation "' . $relationName . '"'
                );
        }

        return $this;
    }

    public function removeRelation($relationName, Document $document = null)
    {
        if (!$this->isRelationExists($relationName)) {
            throw new \Exception('Relation ' . $relationName . ' not configured');
        }

        $relation = $this->relations[$relationName];

        list($relationType, $relatedCollectionName, $field) = $relation;

        $relatedCollection = $this->document
            ->getCollection()
            ->getDatabase()
            ->getCollection($relatedCollectionName);

        if ($document && !$relatedCollection->hasDocument($document)) {
            throw new \Sokil\Mongo\Exception('Document must belongs to related collection');
        }

        switch ($relationType) {
            case Document::RELATION_BELONGS:
                $this->document->unsetField($field)->save();
                break;

            case Document::RELATION_HAS_ONE:
                $document = $this->getRelated($relationName);
                if (!$document) {
                    // relation not exists
                    return $this;
                }
                $document->unsetField($field)->save();
                break;

            case Document::RELATION_HAS_MANY:
                if (!$document) {
                    throw new \Sokil\Mongo\Exception('Related document must be defined');
                }
                $document->unsetField($field)->save();
                break;


            case Document::RELATION_MANY_MANY:
                if (!$document) {
                    throw new \Sokil\Mongo\Exception('Related document must be defined');
                }
                $isRelationListStoredInternally = isset($relation[3]) && $relation[3];
                if ($isRelationListStoredInternally) {
                    $this->document->pull($field, $document->getId())->save();
                } else {
                    $document->pull($field, $this->document->getId())->save();
                }
                break;

            default:
                throw new \Sokil\Mongo\Exception(
                    'Unsupported relation type "' . $relationType . '" when resolve relation "' . $relationName . '"'
                );
        }

        return $this;
    }
}
