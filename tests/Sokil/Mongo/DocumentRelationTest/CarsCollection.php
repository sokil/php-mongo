<?php

namespace Sokil\Mongo\DocumentRelationTest;

class CarsCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null)
    {
        return '\Sokil\Mongo\DocumentRelationTest\CarDocument';
    }
}