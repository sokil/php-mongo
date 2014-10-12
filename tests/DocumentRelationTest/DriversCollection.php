<?php

namespace Sokil\Mongo\DocumentRelationTest;

class DriversCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null)
    {
        return '\Sokil\Mongo\DocumentRelationTest\DriverDocument';
    }
}