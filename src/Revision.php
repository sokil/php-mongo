<?php

namespace Sokil\Mongo;

class Revision extends \Sokil\Mongo\Document
{
    protected $_data = array(
        'document' => array(),
        'date' => null,
    );
    
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $baseCollection;
    
    public function setFromDocument(Document $document)
    {
        $this->set('document', $document->toArray());
        $this->set('date', new \MongoDate);
    }
    
    public function getDocument()
    {
        return $this
            ->baseCollection
            ->getStoredDocumentInstanceFromArray($this->get('document'));
    }
    
    public function getDocumentAsArray()
    {
        return $this->get('document');
    }
    
    public function getDate()
    {
        return $this->get('date');
    }
}