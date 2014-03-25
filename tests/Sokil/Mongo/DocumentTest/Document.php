<?php

namespace Sokil\Mongo\DocumentTest;

class Document extends \Sokil\Mongo\Document
{
    protected $_data = array(
        'status' => 'ACTIVE',
        'profile' => array(
            'name' => 'USER_NAME',
            'birth' => array(
                'year' => 1984,
                'month' => 08,
                'day' => 10,
            )
        ),
    );
}