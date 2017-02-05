<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo;

class Paginator implements \Iterator
{
    private $currentPage = 1;
    
    private $itemsOnPage = 30;
    
    private $totalRowsCount;
    
    /**
     *
     * @var \Sokil\Mongo\Cursor
     */
    private $cursor;
    
    public function __construct(Cursor $cursor = null)
    {
        if ($cursor !== null) {
            $this->setCursor($cursor);
        }
    }
    
    public function __destruct()
    {
        $this->cursor = null;
    }
    
    /**
     *
     * @param int $itemsOnPage
     * @return Paginator
     */
    public function setItemsOnPage($itemsOnPage)
    {
        $this->itemsOnPage = (int) $itemsOnPage;
        
        // define offset
        $this->applyLimits();
        
        return $this;
    }
    
    /**
     *
     * @param int $currentPage
     * @return \Sokil\Mongo\Paginator
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = (int) $currentPage;
        
        // define offset
        $this->applyLimits();
        
        return $this;
    }
    
    public function getCurrentPage()
    {
        // check if current page number greater than max allowed
        $totalPageCount = $this->getTotalPagesCount();
        
        // no document found - page is 1
        if (0 === $totalPageCount) {
            return 1;
        }
        
        if ($this->currentPage <= $totalPageCount) {
            $currentPage = $this->currentPage;
        } else {
            $currentPage = $totalPageCount;
        }
        
        return $currentPage;
    }
    
    /**
     * Define cursor for paginator
     *
     * @param \Sokil\Mongo\Cursor $cursor
     * @return \Sokil\Mongo\Paginator
     */
    public function setCursor(Cursor $cursor)
    {
        $this->cursor = clone $cursor;
        
        $this->applyLimits();
        
        return $this;
    }
    
    public function getTotalRowsCount()
    {
        if (null !== $this->totalRowsCount) {
            return $this->totalRowsCount;
        }
        
        $this->totalRowsCount = $this->cursor->count();
        
        return $this->totalRowsCount;
    }

    /**
     * @return int
     */
    public function getTotalPagesCount()
    {
        return (int) ceil($this->getTotalRowsCount() / $this->itemsOnPage);
    }
    
    private function applyLimits()
    {
        if (!$this->cursor) {
            return;
        }
        
        $currentPage = $this->getCurrentPage();
        
        // get page of rows
        $this->cursor
            ->limit($this->itemsOnPage)
            ->skip(($currentPage - 1) * $this->itemsOnPage);
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->cursor->rewind();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->cursor->valid();
    }

    /**
     * @return Document
     */
    public function current()
    {
        return $this->cursor->current();
    }

    /**
     * @return string
     */
    public function key()
    {
        return $this->cursor->key();
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->cursor->next();
    }
}
