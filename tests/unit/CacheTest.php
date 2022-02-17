<?php

use Ekok\Cache\Cache;

class CacheTest extends \Codeception\Test\Unit
{
    public function testCacheDefault()
    {
        $cache = new Cache();
        $cache->reset();

        $this->assertFalse($cache->isEnabled());
        $this->assertTrue($cache->isDisabled());
        $this->assertNull($cache->getDriver());
        $this->assertNull($cache->getRef());
        $this->assertNull($cache->getDsn());
        $this->assertTrue($cache->has('foo'));
        $this->assertNull($cache->get('foo'));
        $this->assertNull($cache->set('foo', 'bar', null, $saved)->get('foo'));
        $this->assertTrue($saved);
        $this->assertNull($cache->remove('foo', $removed)->get('foo'));
        $this->assertTrue($removed);
        $this->assertSame('cache', $cache->getSeed());
        $this->assertSame($cache, $cache->reset(null, $count));
        $this->assertSame(0, $count);
    }

    public function testCacheEnabled()
    {
        $temp = strtr(sys_get_temp_dir(), '\\', '/') . '/';

        $cache = new Cache(true, 'cache_enabled');
        $cache->reset();

        $this->assertTrue($cache->isEnabled());
        $this->assertFalse($cache->isDisabled());
        $this->assertSame('folder', $cache->getDriver());
        $this->assertSame($temp, $cache->getRef());
        $this->assertSame('folder=' . sys_get_temp_dir(), $cache->getDsn());
        $this->assertFalse($cache->has('foo'));
        $this->assertNull($cache->get('foo'));
        $this->assertSame('bar', $cache->set('foo', 'bar', null, $saved)->get('foo'));
        $this->assertTrue($saved);
        $this->assertFileExists($temp . '/cache_enabled.foo');
        $this->assertSame(array(1, 2, 'bar'), $cache->set('data', array(1, 2, 'bar'), null, $saved)->get('data'));
        $this->assertNull($cache->remove('foo', $removed)->get('foo'));
        $this->assertTrue($removed);
        $this->assertSame('cache_enabled', $cache->getSeed());
        $this->assertSame($cache, $cache->reset(null, $count));
        $this->assertSame(1, $count);
    }

    public function testCacheConfigured()
    {
        $temp = TEST_TMP . '/cache/';

        $cache = new Cache('dir=' . $temp);
        $cache->reset();

        $this->assertTrue($cache->isEnabled());
        $this->assertFalse($cache->isDisabled());
        $this->assertSame('folder', $cache->getDriver());
        $this->assertSame($temp, $cache->getRef());
        $this->assertSame('dir=' . $temp, $cache->getDsn());
        $this->assertFalse($cache->has('foo'));
        $this->assertNull($cache->get('foo'));
        $this->assertSame('bar', $cache->set('foo', 'bar', null, $saved)->get('foo'));
        $this->assertTrue($saved);
        $this->assertFileExists($temp . 'cache.foo');
        $this->assertSame(array(1, 2, 'bar'), $cache->set('data', array(1, 2, 'bar'), null, $saved)->get('data'));
        $this->assertNull($cache->remove('foo', $removed)->get('foo'));
        $this->assertTrue($removed);
        $this->assertSame('cache', $cache->getSeed());
        $this->assertSame($cache, $cache->reset(null, $count));
        $this->assertSame(1, $count);

        // remove expired cache
        $this->assertNull($cache->set('foo', 'bar', -1, $saved)->get('foo'));
        $this->assertFileNotExists($temp . 'cache.foo');
        $this->assertTrue($saved);
    }
}
