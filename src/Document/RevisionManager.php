<?php

namespace Sokil\Mongo\Document;

use Sokil\Mongo\Document;
use Sokil\Mongo\Expression;

class RevisionManager
{

    /**
     * Suffix added to collection name to get name of revisions collection
     * @var string
     */
    const REVISION_COLLECTION_SUFFIX = '.revisions';
    
    /**
     *
     * @var \Sokil\Mongo\Document
     */
    private $document;

    private $revisionsCollection;

    public function __construct(Document $document = null)
    {
        $this->document = $document;
    }

    /**
     * Start listening for changes in document
     */
    public function listen()
    {
        $revisionsCollection = $this->getRevisionsCollection();
        $document = $this->document;

        $createRevisionCallback = function () use ($revisionsCollection, $document) {
            // create new revision
            $revisionsCollection
                ->createDocument()
                ->setDocumentData($document->getOriginalData())
                ->save();
        };

        $this->document->onBeforeUpdate($createRevisionCallback, PHP_INT_MAX);
        $this->document->onBeforeDelete($createRevisionCallback, PHP_INT_MAX);
    }

    /**
     * @return \Sokil\Mongo\Collection
     */
    private function getRevisionsCollection()
    {
        if ($this->revisionsCollection) {
            return $this->revisionsCollection;
        }

        $collection = $this->document->getCollection();
        $revisionsCollectionName = $collection->getName() . self::REVISION_COLLECTION_SUFFIX;

        $this->revisionsCollection = $collection
            ->getDatabase()
            ->map($revisionsCollectionName, array(
                'documentClass' => '\Sokil\Mongo\Revision',
            ))
            ->getCollection($revisionsCollectionName);

        return $this->revisionsCollection;
    }

    public function getRevisions($limit = null, $offset = null)
    {
        $cursor = $this
            ->getRevisionsCollection()
            ->find()
            ->where('__documentId__', $this->document->getId());

        if ($limit) {
            $cursor->limit($limit);
        }

        if ($offset) {
            $cursor->skip($offset);
        }

        return $cursor->findAll();
    }

    /**
     * Get revision by id
     *
     * @param int|string|\MongoId $id
     * @return \Sokil\Mongo\Revision
     */
    public function getRevision($id)
    {
        return $this
            ->getRevisionsCollection()
            ->find()
            ->byId($id)
            ->findOne();
    }

    public function getRevisionsCount()
    {
        return $this
            ->getRevisionsCollection()
            ->find()
            ->where('__documentId__', $this->document->getId())
            ->count();
    }

    public function clearRevisions()
    {
        $documentId = $this->document->getId();
        $this
            ->getRevisionsCollection()
            ->batchDelete(function (Expression $expression) use ($documentId) {
                /* @var $expression \Sokil\Mongo\Expression */
                return $expression->where('__documentId__', $documentId);
            });

        return $this;
    }
}
