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

    public function testGet_NotExistedKey()
    {
        $this->assertNull($this->cache->get('php'));
    }

    public function testGet_ExistedKey()
    {
        $this->cache->setNeverExpired('php', 'Some value');
        $this->cache->setNeverExpired('php', 'PHP: Hypertext Processor');

        $this->assertEquals('PHP: Hypertext Processor', $this->cache->get('php'));
    }

    public function testSetDueDate_NewKey()
    {
        $this->cache->setDueDate('php', 'PHP: Hypertext Processor', time() + 1);

        $this->assertEquals('PHP: Hypertext Processor', $this->cache->get('php'));

        usleep(2000000);

        $this->assertEmpty($this->cache->get('php'));
    }

    public function testSet_NewKey()
    {
        $this->cache->set('php', 'PHP: Hypertext Processor', 1);

        $this->assertEquals('PHP: Hypertext Processor', $this->cache->get('php'));

        usleep(2000000);

        $this->assertEmpty($this->cache->get('php'));
    }

    public function testSet_ExistedKey()
    {
        $this->cache->set('php', 'PHP: Hypertext Processor', 1000);
        $this->cache->set('php', 'PHP: Hypertext Processor', 1);

        $this->assertEquals('PHP: Hypertext Processor', $this->cache->get('php'));

        usleep(2000000);

        $this->assertEmpty($this->cache->get('php'));
    }

    public function testSetDueDate_ExistedKey()
    {
        $this->cache->setDueDate('php', 'Old Value', time() + 100);
        $this->assertEquals('Old Value', $this->cache->get('php'));

        $this->cache->setDueDate('php', 'PHP: Hypertext Processor', time() + 1);
        $this->assertEquals('PHP: Hypertext Processor', $this->cache->get('php'));

        usleep(2000000);

        $this->assertEmpty($this->cache->get('php'));
    }

    public function testDelete()
    {
        $this->cache->set('php', 'PHP: Hypertext Processor', 1);

        $this->assertEquals('PHP: Hypertext Processor', $this->cache->get('php'));

        $this->cache->delete('php');

        $this->assertEmpty($this->cache->get('php'));
    }

    public function testDeleteMatchingTag()
    {
        $this->cache
            ->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'))
            ->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteMatchingTag('compileable');

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('php'));
    }

    public function testDeleteNotMatchingTag()
    {
        $this->cache
            ->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'))
            ->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteNotMatchingTag('compileable');

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('c'));
    }


    public function testDeleteMatchingAllTags()
    {
        $this->cache
            ->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'))
            ->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteMatchingAllTags(array('compileable', 'language'));

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('php'));
    }

    public function testDeleteMatchingNoneOfTags()
    {
        $this->cache
            ->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'))
            ->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteMatchingNoneOfTags(array('language', 'compileable'));

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('c'));
    }


    public function testDeleteMatchingAnyTag()
    {
        $this->cache
            ->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'))
            ->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteMatchingAnyTag(array('compileable', 'elephant'));

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('php'));
    }

    public function testDeleteNotMatchingAnyTag()
    {
        $this->cache
            ->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'))
            ->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteNotMatchingAnyTag(array('compileable', 'elephant'));

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('c'));
    }
}
