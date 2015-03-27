<?php

namespace Sokil\Mongo\Collection;

use Sokil\Mongo\Document;

interface CrudStrategy
{
    public function insert(Document $document);

    public function update(Document $document);
}

