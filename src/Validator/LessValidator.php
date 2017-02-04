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

class LessValidator extends \Sokil\Mongo\Validator
{

    public function validateField(Structure $document, $fieldName, array $params)
    {
        $value = $document->get($fieldName);
        if (!$value) {
            return;
        }

        if (!isset($params['than'])) {
            throw new Exception('Maximum value not specified');
        }

        if ($value >= $params['than']) {
            if (empty($params['message'])) {
                $params['message'] = 'Field "' . $fieldName . '" must be less than specified value in ' . get_called_class();
            }
            $document->addError($fieldName, $this->getName(), $params['message']);
        }
    }
}
