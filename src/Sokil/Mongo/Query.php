<?php

namespace Sokil\Mongo;

class Query
{    
    protected $_query = array();
    
    public static function get()
    {
        return new self;
    }
    
    public function where($field, $value)
    {
        if(!isset($this->_query[$field]) || !is_array($value) || !is_array($this->_query[$field])) {
            $this->_query[$field] = $value;
        }
        else {
            $this->_query[$field] = array_merge_recursive($this->_query[$field], $value);
        }
        
        return $this;
    }
    
    public function whereEmpty($field)
    {
        return $this->where('$or', array(
            array($field => null),
            array($field => ''),
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
        $query = array(
            '$regex'    => $regex,
        );
        
        // options
        $options = '';
        
        if($caseInsensitive) {
            $options .= 'i';
        }
        
        $query['$options'] = $options;
        
        // query
        return $this->where($field, $query);
    }
    
    /**
     * Find documents where the value of a field is an array that contains all the specified elements
     * @param type $field
     * @param array $values
     */
    public function whereAll($field, array $values)
    {
        return $this->where($field, array('$all' => $values));
    }
    
    public function whereElemMatch($field, Query $query)
    {
        return $this->where($field, array('$elemMatch' => $query->toArray()));
    }
    
    public function whereArraySize($field, $length)
    {
        return $this->where($field, array('$size' => (int) $length));
    }
    
    public function toArray()
    {
        return $this->_query;
    }
}