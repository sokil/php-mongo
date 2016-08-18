<?php

namespace Sokil\Mongo\Document;

use Sokil\Mongo\Collection;
use Sokil\Mongo\Document;

class DbRefRelationManager implements RelationManagerInterface
{
    private $relations;

    /**
     *
     * @var \Sokil\Mongo\Document
     */
    private $document;

    private $resolvedRelationIds = array();

    /**
     * @param Document $document
     * @param bool $excludeDb If "false" the DBRef will include the database name. When removing a reference, due to
     * DB inconsistency, the presence of database name creates some issues (database name doesn't match the real DB)
     * @return array
     */
    public static function generateDbRef($document, $excludeDb = false)
    {
        $collection = $document->getCollection();
        $databaseName = $collection->getDatabase()->getName();

        if (!$excludeDb) {
            $dbRef = \MongoDBRef::create($collection->getName(), $document->getId(), $databaseName);
        } else {
            $dbRef = \MongoDBRef::create($collection->getName(), $document->getId());
        }

        return $dbRef;
    }

    /**
     * Is required for creating the references on Many to Many relations, otherwise, the keys "$ref", "$id", "$db" will be transformed in numeric keys.
     *
     * @param Document $document
     * @return \MongoDBRef
     */
    protected function generateMongoDBRef(Document $document)
    {
        $ref = '$ref';
        $id = '$id';
        $db = '$db';

        $collection = $document->getCollection();
        $database = $collection->getDatabase();

        $dbRef = new \MongoDBRef();
        $dbRef->$ref = $collection->getName();
        $dbRef->$id = $document->getId();
        $dbRef->$db = $database->getName();

        return $dbRef;
    }

    public function __construct(Document $document = null)
    {
        $this->document = $document;
        $this->relations = $document->getRelationDefinition();
    }

    /**
     * Check if relation with specified name is configured
     * @param string $name
     * @return boolean
     */
    public function isRelationExists($name)
    {
        return isset($this->relations[$name]);
    }

    /**
     * @param $relationName
     * @return array|null|Document
     * @throws \Sokil\Mongo\Exception
     */
    public function getRelated($relationName)
    {
        // check if relation exists
        if (!$this->isRelationExists($relationName)) {
            throw new \Sokil\Mongo\Exception('Relation with name "'.$relationName.'" not found');
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
                $foreignKey = $relation[2];

                $documentDbRef = self::generateDbRef($this->document);

                $document = $foreignCollection
                    ->find()
                    ->where($foreignKey.'.$id', $this->extractMongoIdFromDbRef($documentDbRef))
                    ->findOne();

                if ($document) {
                    $this->resolvedRelationIds[$relationName] = (string)$document->getId();
                }

                return $document;

            case Document::RELATION_BELONGS:
                $document = null;

                $localKey = $relation[2];

                $dbRef = $this->document->get($localKey);
                $document = $foreignCollection->getDocument($this->extractMongoIdFromDbRef($dbRef));

                if ($document) {
                    $this->resolvedRelationIds[$relationName] = (string)$document->getId();
                }

                return $document;

            case Document::RELATION_HAS_MANY:
                $foreignKey = $relation[2];

                $documentDbRef = self::generateDbRef($this->document);
                $documents = $foreignCollection
                    ->find()
                    ->where($foreignKey.'.$id', $this->extractMongoIdFromDbRef($documentDbRef))
                    ->findAll();

                foreach ($documents as $document) {
                    $this->resolvedRelationIds[$relationName][] = (string)$document->getId();
                }

                return $documents;

            case Document::RELATION_MANY_MANY:
                $isRelationListStoredInternally = isset($relation[3]) && $relation[3];
                $reference = $relation[2];

                if ($isRelationListStoredInternally) {
                    // relation list stored in this document
                    $relatedDbRefsList = $this->document->get($reference);

                    if (!$relatedDbRefsList) {
                        return array();
                    }
                    $relationsMongoIds = array();
                    foreach ($relatedDbRefsList as $relatedDbRef) {
                        $relationsMongoIds[] = $this->extractMongoIdFromDbRef($relatedDbRef);
                    }

                    $documents = $foreignCollection
                        ->find()
                        ->whereIn('_id', $relationsMongoIds)
                        ->findAll();

                } else {
                    // relation list stored in external document
                    $documents = $foreignCollection
                        ->find()
                        ->where($reference.'.$id', $this->document->getId())
                        ->findAll();
                }

                foreach ($documents as $document) {
                    $this->resolvedRelationIds[$relationName][] = (string)$document->getId();
                }

                return $documents;

            default:
                throw new \Sokil\Mongo\Exception(
                    'Unsupported relation type "'.$relationType.'" when resolve relation "'.$relationName.'"'
                );
        }
    }

    public function addRelation($relationName, Document $document)
    {
        if (!$this->isRelationExists($relationName)) {
            throw new \Exception('Relation "'.$relationName.'" not configured');
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
                    throw new \Sokil\Mongo\Exception(
                        'Document '.get_class($document).' must be saved before adding relation'
                    );
                }
                $documentDbRef = self::generateDbRef($document);
                $this->document->set($field, $documentDbRef);
                break;

            case Document::RELATION_HAS_ONE;
                if (!$this->document->isStored()) {
                    throw new \Sokil\Mongo\Exception(
                        'Document '.get_class($this).' must be saved before adding relation'
                    );
                }
                $documentDbRef = self::generateDbRef($this->document);
                $document->set($field, $documentDbRef)->save();
                break;

            case Document::RELATION_HAS_MANY:
                if (!$this->document->isStored()) {
                    throw new \Sokil\Mongo\Exception(
                        'Document '.get_class($this).' must be saved before adding relation'
                    );
                }
                $documentDbRef = self::generateDbRef($this->document);
                $document->set($field, $documentDbRef)->save();
                break;

            case Document::RELATION_MANY_MANY:
                $isRelationListStoredInternally = isset($relation[3]) && $relation[3];
                if ($isRelationListStoredInternally) {
                    $documentDbRef = self::generateMongoDBRef($document);
                    $this->document->push($field, $documentDbRef)->save();
                } else {
                    $documentDbRef = self::generateMongoDBRef($this->document);
                    $document->push($field, $documentDbRef)->save();
                }
                break;

            default:
                throw new \Sokil\Mongo\Exception(
                    'Unsupported relation type "'.$relationType.'" when resolve relation "'.$relationName.'"'
                );
        }

        return $this;
    }

    public function removeRelation($relationName, Document $document = null)
    {
        if (!$this->isRelationExists($relationName)) {
            throw new \Exception('Relation '.$relationName.' not configured');
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

            case Document::RELATION_HAS_ONE;
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
                    $documentDbRef = self::generateDbRef($document, true);
                    $this->document->pull($field, $documentDbRef)->save();
                } else {
                    $documentDbRef = self::generateDbRef($this->document, true);
                    $document->pull($field, $documentDbRef)->save();
                }
                break;

            default:
                throw new \Sokil\Mongo\Exception(
                    'Unsupported relation type "'.$relationType.'" when resolve relation "'.$relationName.'"'
                );
        }

        return $this;
    }

    /**
     * @param $dbRef
     * @return mixed
     */
    protected function extractMongoIdFromDbRef($dbRef)
    {
        if (is_array($dbRef) && array_key_exists('$id', $dbRef)) {
            return $dbRef['$id'];
        } else {
            if (get_class($dbRef) === 'MongoDBRef') {
                $id = '$id';

                return $dbRef->$id;
            }
        }
    }
}
