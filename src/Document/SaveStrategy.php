<?php

namespace Sokil\Mongo\Document;

use Sokil\Mongo\Document;

abstract class SaveStrategy
{
    /**
     * @var \Sokil\Mongo\Document
     */
    protected $document;
    
    public function __construct(Document $document) {
        $this->document = $document;
    }
    
    public function save($validate)
    {
        // if document already in db and not modified - skip this method
        if (!$this->document->isSaveRequired()) {
            return $this;
        }

        if ($validate) {
            $this->document->validate();
        }

        // handle beforeSave event
        if($this->document->triggerEvent('beforeSave')->isCancelled()) {
            return $this;
        }

        if ($this->document->isStored()) {
            if($this->document->triggerEvent('beforeUpdate')->isCancelled()) {
                return $this;
            }
            $this->insert();
            $this->document->triggerEvent('afterUpdate');
        } else {
            if($this->document->triggerEvent('beforeInsert')->isCancelled()) {
                return $this;
            }
            $this->update();
            $this->document->triggerEvent('afterInsert');
        }

        // handle afterSave event
        $this->document->triggerEvent('afterSave');
        
        $this->document->apply();
    }
}

