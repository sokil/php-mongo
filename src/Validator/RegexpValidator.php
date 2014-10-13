<?php

namespace Sokil\Mongo\Validator;

class RegexpValidator extends \Sokil\Mongo\Validator
{

    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        if (!$document->get($fieldName)) {
            return;
        }

        if (preg_match($params['pattern'], $document->get($fieldName))) {
            return;
        }
        
        if (!isset($params['message'])) {
            $params['message'] = 'Field "' . $fieldName . '" not match regexp ' . $params['pattern'] . ' in model ' . get_called_class();
        }

        $document->addError($fieldName, $this->getName(), $params['message']);
    }

}
