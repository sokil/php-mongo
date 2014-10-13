<?php

namespace Sokil\Mongo;

abstract class Validator
{
    public function getName()
    {
        $class = explode('\\', get_called_class());
        return strtolower(array_pop($class));
    }
    
    abstract public function validateField(Document $document, $fieldName, array $params);
    
    public function validate(Document $document, array $fieldNameList, array $params)
    {
        foreach($fieldNameList as $fieldName) {           
            $this->validateField($document, $fieldName, $params);
        }
    }
}