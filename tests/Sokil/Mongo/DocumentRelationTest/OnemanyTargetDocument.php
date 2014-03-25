<?php

namespace Sokil\Mongo\DocumentRelationTest;

class OnemanyTargetDocument extends \Sokil\Mongo\Document
{
    protected $_data = array(
        'source_id' => null,
    );
    
    public function relations()
    {
        return array(
            'belongs'   => array(self::RELATION_BELONGS, 'source', 'source_id'),
        );
    }
}