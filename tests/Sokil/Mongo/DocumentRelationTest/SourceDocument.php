<?php

namespace Sokil\Mongo\DocumentRelationTest;

class SourceDocument extends \Sokil\Mongo\Document
{    
    public function relations()
    {
        return array(
            'hasOne'    => array(self::RELATION_HAS_ONE, 'oneoneTarget', 'source_id'),
            'hasMany'   => array(self::RELATION_HAS_MANY, 'onemanyTarget', 'source_id'),
        );
    }
}