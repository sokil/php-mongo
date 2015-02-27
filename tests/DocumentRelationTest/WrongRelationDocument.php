<?php

namespace Sokil\Mongo\DocumentRelationTest;

class WrongRelationDocument extends \Sokil\Mongo\Document
{    
    public function relations()
    {
        return array(
            'engine'    => array('WRONG_RELATION_TYPE', 'engines', 'some_id'),
        );
    }
}