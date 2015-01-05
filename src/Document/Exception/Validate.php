<?php 

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo\Document\Exception;

use Sokil\Mongo\Document;

/**
 * Validator exception
 *
 * @author Dmytro Sokil <dmytro.sokil@gmail.com>
 */
class Validate extends \Sokil\Mongo\Exception
{
    /**
     *
     * @var \Sokil\Mongo\Document
     */
    private $_document;
    
    public function setDocument(Document $document)
    {
        $this->_document = $document;
        return $this;
    }
    
    public function getDocument()
    {
        return $this->_document;
    }
}