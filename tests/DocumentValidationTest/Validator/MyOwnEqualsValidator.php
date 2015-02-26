<?php

namespace Sokil\Mongo\DocumentValidationTest\Validator;

class MyOwnEqualsValidator extends \Sokil\Mongo\Validator
{
    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        if (!$document->get($fieldName)) {
            return;
        }

        if ($document->get($fieldName) === $params['to']) {
            return;
        }

        if (!isset($params['message'])) {
            $params['message'] = 'Not equals';
        }
        
        $document->addError($fieldName, $this->getName(), $params['message']);
    }
}