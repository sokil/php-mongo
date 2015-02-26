<?php

namespace Sokil\Mongo\DocumentValidationTest;

class ValidatorMethodDocument extends \Sokil\Mongo\Document
{

    public function rules()
    {
        return array(
            array('field', 'validateEquals42'),
        );
    }

    public function validateEquals42($fieldName, array $params)
    {
        if (42 !== $this->get($fieldName)) {
            $errorMessage = isset($params['message']) ? $params['message'] : 'Not equals to 42';

            $this->addError($fieldName, 'validateEquals42', $errorMessage);
        }
    }

}