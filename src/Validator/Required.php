<?php

namespace Sokil\Mongo\Validator;

class Required extends \Sokil\Mongo\Validator
{
    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        var_dump($document->getErrors());
        if ($document->get($fieldName)) {
            return;
        }
        
        $errorMessage = isset($params['message'])
            ? $params['message']
            : 'Field "' . $fieldName . '" required in model ' . get_class($document);

        $document->addError(
            $fieldName, 
            $this->getName(), 
            $errorMessage
        );
    }
}