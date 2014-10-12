<?php

namespace Sokil\Mongo;

abstract class Validator
{
    abstract public function validateField(Document $document, $fieldName);
    
    public function validate(Document $document, array $fieldNameList)
    {
        foreach($fieldNameList as $fieldName) {           
            $this->validateField($document, $fieldName);
        }
    }
}