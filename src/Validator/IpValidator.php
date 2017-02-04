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

class IpValidator extends \Sokil\Mongo\Validator
{
    public function validateField(Structure $document, $fieldName, array $params)
    {
        $value = $document->get($fieldName);
        
        // check only if set
        if (!$value) {
            return;
        }

        // check if url valid
        if (false !== filter_var($value, FILTER_VALIDATE_IP)) {
            return;
        }
        
        if (!isset($params['message'])) {
            $params['message'] = 'Value of field "' . $fieldName . '" is not valid IP address in model ' . get_called_class();
        }

        $document->addError($fieldName, $this->getName(), $params['message']);
    }
}
