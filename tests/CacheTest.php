<?php

namespace Sokil\Mongo;

class CacheTest extends \PHPUnit_Framework_TestCase
{    
    /**
     *
     * @var \Sokil\Mongo\Cache
     */
    private $cache;
    
    public function setUp()
    {
        $client = new Client();
        $this->cache = $client
            ->getDatabase('test')
            ->getCache('cache_namespace')
            ->init();
    }
    
    public function tearDown()
    {
        $this->cache->clear();
    }
    
    public function testDeleteMatchingAnyTag()
    {
        $this->cache
            ->setNeverExpired('php', 'PHP: Hypertext Procesor', ['language', 'interpretable'])
            ->setNeverExpired('c', 'C', ['language', 'compileable']);
        
        $this->assertEquals(2, count($this->cache));
        
        $this->cache->deleteMatchingAnyTag(['compileable', 'elephant']);
        
        $this->assertEquals(1, count($this->cache));
        
        $this->assertTrue($this->cache->has('php'));
    }
}