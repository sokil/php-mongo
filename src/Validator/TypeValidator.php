<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo\Validator;

use Sokil\Mongo\Structure;

class TypeValidator extends \Sokil\Mongo\Validator
{

    public function validateField(Structure $document, $fieldName, array $params)
    {
        $value = $document->get($fieldName);
        if (!$value) {
            return;
        }

        if (empty($params[0])) {
            throw new Exception('Type not specified');
        }
        
        $requiredTypes = (array) $params[0];
        
        $allowedTypes = array(
            'array',
            'bool',
            'callable',
            'double',
            'float',
            'int',
            'integer',
            'long',
            'null',
            'numeric',
            'object',
            'real',
            'resource',
            'scalar',
            'string',
        );
        
        foreach ($requiredTypes as $type) {
            if (!in_array($type, $allowedTypes)) {
                throw new Exception('Type must be one of ' . implode(', ', $allowedTypes));
            }
            if (true === call_user_func('is_' . $type, $value)) {
                return;
            }
        }
        
        if (!isset($params['message'])) {
            if (count($requiredTypes) === 1) {
                $params['message'] = 'Field "' . $fieldName . '" must be of type ' . $requiredTypes[0] . ' in ' . get_called_class();
            } else {
                $params['message'] = 'Field "' . $fieldName . '" must be one of types ' . implode(', ', $requiredTypes) . ' in ' . get_called_class();
            }
        }

        $document->addError($fieldName, $this->getName(), $params['message']);
    }
}
