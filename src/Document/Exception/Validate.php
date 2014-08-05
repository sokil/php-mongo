<?php 

namespace Sokil\Mongo\Document\Exception;

use Sokil\Mongo\Document;

class Validate extends \Sokil\Mongo\Exception
{
    /**
     *
     * @var \Sokil\Mongo\Document
     */
    private $_document;
    
    public function setDocument(Document $document)
    {
        $this->_document = $document;
        return $this;
    }
    
    public function getDocument()
    {
        return $this->_document;
    }
}