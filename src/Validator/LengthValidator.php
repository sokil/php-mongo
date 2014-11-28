<?php

namespace Sokil\Mongo\Validator;

class LengthValidator extends \Sokil\Mongo\Validator
{

    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        $value = $document->get($fieldName);

        if (!$value) {
            return;
        }

        $length = mb_strlen($value);

        // check if field is of specified length
        if(isset($params['is'])) {
            if($length === $params['is']) {
                return;
            }

            if (!isset($params['message'])) {
                $params['message'] = 'Field "' . $fieldName . '" length not equal to ' . $params['is'] . ' in model ' . get_called_class();
            }

            $document->addError($fieldName, $this->getName(), $params['message']);
            return;
        }

        // check if fied is shorter than required
        if(isset($params['min'])) {
            if($length < $params['min']) {
                if (!isset($params['messageTooShort'])) {
                    $params['messageTooShort'] = 'Field "' . $fieldName . '" length is shorter tnan ' . $params['min'] . ' in model ' . get_called_class();
                }

                $document->addError($fieldName, $this->getName(), $params['messageTooShort']);
                return;
            }
        }

        // check if fied is longer than required
        if(isset($params['max'])) {
            if($length > $params['max']) {
                if (!isset($params['messageTooLong'])) {
                    $params['messageTooLong'] = 'Field "' . $fieldName . '" length is longer tnan ' . $params['max'] . ' in model ' . get_called_class();
                }

                $document->addError($fieldName, $this->getName(), $params['messageTooLong']);
                return;
            }
        }
    }

}
