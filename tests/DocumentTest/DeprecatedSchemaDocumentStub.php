<?php

namespace Sokil\Mongo\DocumentTest;

use \Sokil\Mongo\Document;

class DeprecatedSchemaDocumentStub extends Document
{
    protected $schema = array(
        'status' => 'ACTIVE',
        'profile' => array(
            'name' => 'USER_NAME',
            'birth' => array(
                'year' => 1984,
                'month' => 8,
                'day' => 10,
            )
        ),
        'interests' => 'none',
        'languages' => array('php'),
    );

}