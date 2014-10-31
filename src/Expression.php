<?php

namespace Sokil\Mongo;

/**
 * This class represents all expressions used to query document from collection
 * 
 * @link http://docs.mongodb.org/manual/reference/operator/query/
 * @author Dmytro Sokil <dmytro.sokil@gmail.com>
 */
class Expression
{    
    protected $_expression = array();
    
    /**
     * Create new instance of expression
     * @return \Sokil\Mongo\Expression
     */
    public function expression()
    {        
        return new self;
    }
    
    public function where($field, $value)
    {
        if(!isset($this->_expression[$field]) || !is_array($value) || !is_array($this->_expression[$field])) {
            $this->_expression[$field] = $value;
        }
        else {
            $this->_expression[$field] = array_merge_recursive($this->_expression[$field], $value);
        }
        
        return $this;
    }
    
    public function whereEmpty($field)
    {
        return $this->where('$or', array(
            array($field => null),
            array($field => ''),
            array($field => array()),
            array($field => array('$exists' => false))
        ));
    }
    
    public function whereNotEmpty($field)
    {
        return $this->where('$nor', array(
            array($field => null),
            array($field => ''),
            array($field => array()),
            array($field => array('$exists' => false))
        ));
    }
    
    public function whereGreater($field, $value)
    {
        return $this->where($field, array('$gt' => $value));
    }
    
    public function whereGreaterOrEqual($field, $value)
    {
        return $this->where($field, array('$gte' => $value));
    }
    
    public function whereLess($field, $value)
    {
        return $this->where($field, array('$lt' => $value));
    }
    
    public function whereLessOrEqual($field, $value)
    {
        return $this->where($field, array('$lte' => $value));
    }
    
    public function whereNotEqual($field, $value)
    {
        return $this->where($field, array('$ne' => $value));
    }
    
    /**
     * Selects the documents where the value of a 
     * field equals any value in the specified array.
     * 
     * @param type $field
     * @param array $values
     * @return type
     */
    public function whereIn($field, array $values)
    {
        return $this->where($field, array('$in' => $values));
    }
    
    public function whereNotIn($field, array $values)
    {
        return $this->where($field, array('$nin' => $values));
    }
    
    public function whereExists($field)
    {
        return $this->where($field, array('$exists' => true));
    }
    
    public function whereNotExists($field)
    {
        return $this->where($field, array('$exists' => false));
    }
    
    public function whereHasType($field, $type)
    {
        return $this->where($field, array('$type' => (int) $type));
    }
    
    public function whereDouble($field)
    {
        return $this->whereHasType($field, Document::FIELD_TYPE_DOUBLE);
    }
    
    public function whereString($field)
    {
        return $this->whereHasType($field, Document::FIELD_TYPE_STRING);
    }
    
    public function whereObject($field)
    {
        return $this->whereHasType($field, Document::FIELD_TYPE_OBJECT);
    }
    
    public function whereBoolean($field)
    {
        return $this->whereHasType($field, Document::FIELD_TYPE_BOOLEAN);
    }
    
    public function whereArray($field)
    {
        return $this->whereJsCondition('Array.isArray(this.' . $field . ')');
    }
    
    public function whereArrayOfArrays($field)
    {
        return $this->whereHasType($field, Document::FIELD_TYPE_ARRAY);
    }
    
    public function whereObjectId($field)
    {
        return $this->whereHasType($field, Document::FIELD_TYPE_OBJECT_ID);
    }
    
    public function whereDate($field)
    {
        return $this->whereHasType($field, Document::FIELD_TYPE_DATE);
    }
    
    public function whereNull($field)
    {
        return $this->whereHasType($field, Document::FIELD_TYPE_NULL);
    }
    
    public function whereJsCondition($condition)
    {
        return $this->where('$where', $condition);
    }
    
    public function whereLike($field, $regex, $caseInsensitive = true)
    {
        // regex
        $expression = array(
            '$regex'    => $regex,
        );
        
        // options
        $options = '';
        
        if($caseInsensitive) {
            $options .= 'i';
        }
        
        $expression['$options'] = $options;
        
        // query
        return $this->where($field, $expression);
    }
    
    /**
     * Find documents where the value of a field is an array 
     * that contains all the specified elements.
     * This is equivalent of logical AND.
     * 
     * @link http://docs.mongodb.org/manual/reference/operator/query/all/
     * 
     * @param string $field point-delimited field name
     * @param array $values
     * @return \Sokil\Mongo\Expression
     */
    public function whereAll($field, array $values)
    {
        return $this->where($field, array('$all' => $values));
    }
    
    /**
     * Find documents where the value of a field is an array 
     * that contains any of the specified elements.
     * This is equivalent of logical AND.
     * 
     * @param string $field point-delimited field name
     * @param array $values
     * @return \Sokil\Mongo\Expression
     */
    public function whereAny($field, array $values)
    {
        return $this->whereIn($field, $values);
    }

    /**
     * Matches documents in a collection that contain an array field with at 
     * least one element that matches all the specified query criteria.
     * 
     * @param string $field point-delimited field name
     * @param \Sokil\Mongo\Expression|callable|array $expression
     * @return \Sokil\Mongo\Expression
     */
    public function whereElemMatch($field, $expression)
    {
        if(is_callable($expression)) {
            $expression = call_user_func($expression, $this->expression());
        }
        
        if($expression instanceof Expression) {
            $expression = $expression->toArray();
        } elseif(!is_array($expression)) {
            throw new Exception('Wrong expression passed');
        }
        
        return $this->where($field, array('$elemMatch' => $expression));
    }
    
    /**
     * Matches documents in a collection that contain an array field with elements
     * that do not matches all the specified query criteria.
     * 
     * @param type $field
     * @param \Sokil\Mongo\Expression|callable|array $expression
     * @return type
     */
    public function whereElemNotMatch($field, $expression)
    {
        return $this->whereNot($this->expression()->whereElemMatch($field, $expression));
    }
    
    /**
     * Selects documents if the array field is a specified size.
     * 
     * @param type $field
     * @param type $length
     * @return type
     */
    public function whereArraySize($field, $length)
    {
        return $this->where($field, array('$size' => (int) $length));
    }
    
    /**
     * Selects the documents that satisfy at least one of the expressions
     *
     * @param array|\Sokil\Mongo\Expression $expressions Array of Expression instances or comma delimited expression list
     * @return \Sokil\Mongo\Expression
     */
    public function whereOr($expressions = null /**, ...**/)
    {
        if($expressions instanceof Expression) {
            $expressions = func_get_args();
        }
        
        return $this->where('$or', array_map(function(Expression $expression) {
            return $expression->toArray();
        }, $expressions));
    }
    
    /**
     * Select the documents that satisfy all the expressions in the array
     *
     * @param array|\Sokil\Mongo\Expression $expressions Array of Expression instances or comma delimited expression list
     * @return \Sokil\Mongo\Expression
     */
    public function whereAnd($expressions = null /**, ...**/)
    {
        if($expressions instanceof Expression) {
            $expressions = func_get_args();
        }
        
        return $this->where('$and', array_map(function(Expression $expression) {
            return $expression->toArray();
        }, $expressions));
    }
    
    /**
     * Selects the documents that fail all the query expressions in the array
     *
     * @param array|\Sokil\Mongo\Expression $expressions Array of Expression instances or comma delimited expression list
     * @return \Sokil\Mongo\Expression
     */
    public function whereNor($expressions = null /**, ...**/)
    {
        if($expressions instanceof Expression) {
            $expressions = func_get_args();
        }
        
        return $this->where('$nor', array_map(function(Expression $expression) {
            return $expression->toArray();
        }, $expressions));
    }
    
    public function whereNot(Expression $expression)
    {
        foreach($expression->toArray() as $field => $value) {
            // $not acceptable only for operators-expressions
            if(is_array($value) && is_string(key($value))) {
                $this->where($field, array('$not' => $value));
            }
            // for single values use $ne
            else {
                $this->whereNotEqual($field, $value);
            }
        }
        
        return $this;
    }
    
    public function toArray()
    {
        return $this->_expression;
    }
    
    public function merge(Expression $expression)
    {
        $this->_expression = array_merge_recursive($this->_expression, $expression->toArray());
        return $this;
    }
}