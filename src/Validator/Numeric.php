<?php

namespace Sokil\Mongo\Validator;

class Numeric extends \Sokil\Mongo\Validator
{

    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        if (!$document->get($fieldName)) {
            return;
        }

        if (is_numeric($document->get($fieldName))) {
            return;
        }
        
        if (!isset($params['message'])) {
            $params['message'] = 'Field "' . $fieldName . '" not numeric in model ' . get_called_class();
        }

        $document->addError($fieldName, $this->getName(), $params['message']);
    }

}
