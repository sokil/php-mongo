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

class InValidator extends \Sokil\Mongo\Validator
{

    public function validateField(Structure $document, $fieldName, array $params)
    {
        if (!$document->get($fieldName)) {
            return;
        }

        if (in_array($document->get($fieldName), $params['range'])) {
            return;
        }
        
        if (!isset($params['message'])) {
            $params['message'] = 'Field "' . $fieldName . '" not in range of allowed values in model ' . get_called_class();
        }

        $document->addError($fieldName, $this->getName(), $params['message']);
    }
}
