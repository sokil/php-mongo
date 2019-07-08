<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo;

class Operator implements ArrayableInterface
{
        /**
     *
     * @var array list of update operations
     */
    private $operators = array();
    
    public function set($fieldName, $value)
    {
        if (!isset($this->operators['$set'])) {
            $this->operators['$set'] = array();
        }
        
        $this->operators['$set'][$fieldName] = Structure::prepareToStore($value);
        
        return $this;
    }
    
    public function push($fieldName, $value)
    {
        // prepare to store
        $value = Structure::prepareToStore($value);
        
        // no $push operator found
        if (!isset($this->operators['$push'])) {
            $this->operators['$push'] = array();
        }
        
        if (!isset($this->operators['$push'][$fieldName])) {
            // no field name found
            $this->operators['$push'][$fieldName] = $value;
        } else {
            $oldValue = $this->operators['$push'][$fieldName];
            if (!is_array($oldValue) || !isset($oldValue['$each'])) {
                // field name found and has single value
                $this->operators['$push'][$fieldName] = array(
                    '$each' => array($oldValue, $value)
                );
            } else {
                // field name found and already $each
                $this->operators['$push'][$fieldName]['$each'][] = $value;
            }
        }

        return $this;
    }
    
    public function pushEach($fieldName, array $values)
    {
        // value must be list, not dictionary
        $values = array_values($values);

        // prepare to store
        $values = Structure::prepareToStore($values);

        // no $push operator found
        if (!isset($this->operators['$push'])) {
            $this->operators['$push'] = array();
        }
        
        // no field name found
        if (!isset($this->operators['$push'][$fieldName])) {
            $this->operators['$push'][$fieldName] = array(
                '$each' => $values
            );
        } // field name found and has single value
        elseif (!is_array($this->operators['$push'][$fieldName]) || !isset($this->operators['$push'][$fieldName]['$each'])) {
            $oldValue = $this->operators['$push'][$fieldName];
            $this->operators['$push'][$fieldName] = array(
                '$each' => array_merge(array($oldValue), $values)
            );
        } // field name found and already $each
        else {
            $this->operators['$push'][$fieldName]['$each'] = array_merge(
                $this->operators['$push'][$fieldName]['$each'],
                $values
            );
        }

        return $this;
    }

    /**
     * The $slice modifier limits the number of array elements during a
     * $push operation. To project, or return, a specified number of array
     * elements from a read operation, see the $slice projection operator instead.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/update/slice
     * @param string $field
     * @param int $slice
     * @return \Sokil\Mongo\Operator
     * @throws \Sokil\Mongo\Exception
     */
    public function pushEachSlice($field, $slice)
    {
        $slice = (int) $slice;
        
        if (!isset($this->operators['$push'][$field]['$each'])) {
            throw new Exception('Field ' . $field . ' must be pushed wit $each modifier');
        }
        
        $this->operators['$push'][$field]['$slice'] = $slice;
        
        return $this;
    }

    /**
     * The $sort modifier orders the elements of an array during a $push operation.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/update/sort
     * @param string $field
     * @param array $sort
     * @return \Sokil\Mongo\Operator
     * @throws \Sokil\Mongo\Exception
     */
    public function pushEachSort($field, array $sort)
    {
        // add modifiers
        if (empty($sort)) {
            throw new Exception('Sort condition is empty');
        }
        
        if (!isset($this->operators['$push'][$field]['$each'])) {
            throw new Exception('Field ' . $field . ' must be pushed with $each modifier');
        }
        
        $this->operators['$push'][$field]['$sort'] = $sort;
        
        return $this;
    }

    /**
     * The $position modifier specifies the location in the array at which
     * the $push operator insert elements. Without the $position modifier,
     * the $push operator inserts elements to the end of the array. See
     * $push modifiers for more information.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/update/position
     * @param string $field
     * @param int $position non-negative number that corresponds to the position in the array, based on a zero-based index
     * @return \Sokil\Mongo\Operator
     * @throws \Sokil\Mongo\Exception
     */
    public function pushEachPosition($field, $position)
    {
        $position = (int) $position;
        
        // add modifiers
        if ($position <= 0) {
            throw new Exception('Position must be greater 0');
        }
        
        if (!isset($this->operators['$push'][$field]['$each'])) {
            throw new Exception('Field ' . $field . ' must be pushed with $each modifier');
        }
        
        $this->operators['$push'][$field]['$position'] = $position;
        
        return $this;
    }

    public function addToSet($field, $value)
    {
        // new field
        if (!isset($this->operators['$addToSet'][$field])) {
            $this->operators['$addToSet'][$field] = $value;
            return $this;
        }

        // scalar value or array in existed field
        if (!is_array($this->operators['$addToSet'][$field]) || !isset($this->operators['$addToSet'][$field]['$each'])) {
            $this->operators['$addToSet'][$field] = array(
                '$each' => array(
                    $this->operators['$addToSet'][$field],
                    $value,
                ),
            );
            return $this;
        }

        // field already $each
        $this->operators['$addToSet'][$field]['$each'][] = $value;

        return $this;
    }

    public function addToSetEach($field, array $values)
    {
        // new field
        if (!isset($this->operators['$addToSet'][$field])) {
            $this->operators['$addToSet'][$field]['$each'] = $values;
            return $this;
        }

        // scalar value or array in existed field
        if (!is_array($this->operators['$addToSet'][$field]) || !isset($this->operators['$addToSet'][$field]['$each'])) {
            $this->operators['$addToSet'][$field] = array(
                '$each' => array_merge(
                    array($this->operators['$addToSet'][$field]),
                    $values
                ),
            );
            return $this;
        }

        // field already $each
        $this->operators['$addToSet'][$field] = array(
            '$each' => array_merge(
                $this->operators['$addToSet'][$field]['$each'],
                $values
            ),
        );

        return $this;
    }

    public function increment($fieldName, $value = 1)
    {
        // check if update operations already added
        $oldIncrementValue = $this->get('$inc', $fieldName);
        if ($oldIncrementValue) {
            $value = $oldIncrementValue + $value;
        }
        
        $this->operators['$inc'][$fieldName] = $value;
        
        return $this;
    }

    /**
     * The $pull operator removes from an existing array all instances of a
     * value or values that match a specified query.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/update/pull
     * @param integer|string|\Sokil\Mongo\Expression|callable $expression
     * @param mixed|\Sokil\Mongo\Expression|callable $value
     * @return \Sokil\Mongo\Operator
     */
    public function pull($expression, $value = null)
    {
        // field-value pulling
        if ($value) {
            // expression
            if (is_callable($value)) {
                $configurator = $value;
                $value = new Expression();
                call_user_func($configurator, $value);
            }

            if ($value instanceof Expression) {
                $value = $value->toArray();
            }
            
            $this->operators['$pull'][$expression] = $value;
            
            return $this;
        }

        // expression
        if (is_callable($expression)) {
            $configurator = $expression;
            $expression = new Expression();
            call_user_func($configurator, $expression);
        }

        if ($expression instanceof Expression) {
            $expression = $expression->toArray();
        } elseif (!is_array($expression)) {
            throw new \InvalidArgumentException('Expression must be field name, callable or Expression object');
        }
        
        if (!isset($this->operators['$pull'])) {
            // no $pull operator found
            $this->operators['$pull'] = $expression;
        } else {
            // $pull operator found
            $this->operators['$pull'] = array_merge($this->operators['$pull'], $expression);
        }
        
        return $this;
    }

    /**
     * The $unset operator deletes a particular field
     *
     * @link http://docs.mongodb.org/manual/reference/operator/update/unset
     * @param string $fieldName
     * @return \Sokil\Mongo\Operator
     */
    public function unsetField($fieldName)
    {
        $this->operators['$unset'][$fieldName] = '';
        return $this;
    }

    /**
     * The $rename operator deletes a particular field
     *
     * @link https://docs.mongodb.com/manual/reference/operator/update/rename/
     *
     * @param string $oldFieldName
     * @param string $newFieldName
     *
     * @return Operator
     */
    public function renameField($oldFieldName, $newFieldName)
    {
        $this->operators['$rename'][$oldFieldName] = $newFieldName;

        return $this;
    }
    
    public function bitwiceAnd($field, $value)
    {
        $this->operators['$bit'][$field]['and'] = (int) $value;
        return $this;
    }
    
    public function bitwiceOr($field, $value)
    {
        $this->operators['$bit'][$field]['or'] = (int) $value;
        return $this;
    }
    
    public function bitwiceXor($field, $value)
    {
        $this->operators['$bit'][$field]['xor'] = (int) $value;
        
        return $this;
    }
    
    public function isDefined()
    {
        return (bool) $this->operators;
    }
    
    public function reset()
    {
        $this->operators = array();
        return $this;
    }
    
    public function get($operation, $fieldName = null)
    {
        if ($fieldName) {
            return isset($this->operators[$operation][$fieldName])
                ? $this->operators[$operation][$fieldName]
                : null;
        }
        
        return isset($this->operators[$operation])
            ? $this->operators[$operation]
            : null;
    }

    /**
     * @deprecated since v.1.13 use Operator::toArray()
     * @return array
     */
    public function getAll()
    {
        return $this->operators;
    }

    public function toArray()
    {
        return $this->operators;
    }
    
    public function isReloadRequired()
    {
        return isset($this->operators['$inc']) || isset($this->operators['$pull']);
    }

    /**
     * Transform operator in different formats to canonical array form
     *
     * @param mixed $mixed
     * @return array
     * @throws \Sokil\Mongo\Exception
     */
    public static function convertToArray($mixed)
    {
        // get operator from callable
        if (is_callable($mixed)) {
            $callable = $mixed;
            $mixed = new self();
            call_user_func($callable, $mixed);
        }

        // get operator array
        if ($mixed instanceof ArrayableInterface && $mixed instanceof self) {
            $mixed = $mixed->toArray();
        } elseif (!is_array($mixed)) {
            throw new Exception('Mixed must be instance of Operator');
        }

        return $mixed;
    }
}
