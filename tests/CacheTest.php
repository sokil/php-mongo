<?php

namespace Sokil\Mongo;

use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{    
    /**
     *
     * @var \Sokil\Mongo\Cache
     */
    private $cache;
    
    public function setUp()
    {
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);

        $this->cache = $client
            ->getDatabase('test')
            ->getCache('cache_namespace')
            ->init();
    }
    
    public function tearDown()
    {
        if($this->cache) {
            $this->cache->clear();
        }
    }

    public function getGetMultipleDataProvider()
    {
        return array(
            array(
                array('key1' => 'value1', 'key2' => 'value2'),
            ),
            array(
                array('key1' => 'value1', 'unexistedKey' => 'defValue'),
            ),
            array(
                array('unexistedKey1' => 'defValue', 'unexistedKey2' => 'defValue'),
            ),
        );
    }

    /**
     * @dataProvider getGetMultipleDataProvider
     */
    public function testGetMultiple(array $expectedValues)
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $keys = array_keys($expectedValues);
        $actualValues = $this->cache->getMultiple($keys, 'defValue');
        $this->assertEquals($expectedValues, $actualValues);
    }

    public function getSetMultipleDataProvider()
    {
        return array(
            array(
                array('key1' => 'value1', 'key2' => 'value2'),
            ),
            array(
                array('key1' => 'value1', 'unexistedKey' => 'defValue'),
            ),
            array(
                array('unexistedKey1' => 'defValue', 'unexistedKey2' => 'defValue'),
            ),
        );
    }

    /**
     * @dataProvider getSetMultipleDataProvider
     */
    public function testSetMultiple($expectedValues)
    {
        $this->cache->setMultiple(array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ));

        $keys = array_keys($expectedValues);
        $actualValues = $this->cache->getMultiple($keys, 'defValue');
        $this->assertEquals($expectedValues, $actualValues);
    }

    public function testGet_NotExistedKey()
    {
        $this->assertNull($this->cache->get('php'));
    }

    public function testGet_ExistedKey()
    {
        $this->cache->setNeverExpired('php', 'Some value');
        
        $this->assertEquals('Some value', $this->cache->get('php'));
        
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

    public function testDeleteMultiple()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $actualValues = $this->cache->deleteMultiple(array('key1', 'key3'));

        $this->assertEquals(1, count($this->cache));
        $this->assertEquals('value2', $this->cache->get('key2'));
    }

    public function testDeleteMatchingTag()
    {
        $this->cache->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'));
        $this->cache->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteMatchingTag('compileable');

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('php'));
    }

    public function testDeleteNotMatchingTag()
    {
        $this->cache->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'));
        $this->cache->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteNotMatchingTag('compileable');

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('c'));
    }


    public function testDeleteMatchingAllTags()
    {
        $this->cache->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'));
        $this->cache->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteMatchingAllTags(array('compileable', 'language'));

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('php'));
    }

    public function testDeleteMatchingNoneOfTags()
    {
        $this->cache->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'));
        $this->cache->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteMatchingNoneOfTags(array('language', 'compileable'));

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('c'));
    }


    public function testDeleteMatchingAnyTag()
    {
        $this->cache->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'));
        $this->cache->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteMatchingAnyTag(array('compileable', 'elephant'));

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('php'));
    }

    public function testDeleteNotMatchingAnyTag()
    {
        $this->cache->setNeverExpired('php', 'PHP: Hypertext Processor', array('language', 'interpretable'));
        $this->cache->setNeverExpired('c', 'C', array('language', 'compileable'));

        $this->assertEquals(2, count($this->cache));

        $this->cache->deleteNotMatchingAnyTag(array('compileable', 'elephant'));

        $this->assertEquals(1, count($this->cache));

        $this->assertTrue($this->cache->has('c'));
    }
}
