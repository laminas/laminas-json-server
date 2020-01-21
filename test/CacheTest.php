<?php

/**
 * @see       https://github.com/laminas/laminas-json-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-json-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-json-server/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Json\Server;

use Laminas\Json\Server;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function is_writable;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class CacheTest extends TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->server = new Server\Server();
        $this->server->setClass(TestAsset\Foo::class, 'foo');
        $this->cacheFile = tempnam(sys_get_temp_dir(), 'zjs');

        // if (!is_writeable(__DIR__)) {
        if (! is_writable($this->cacheFile)) {
            $this->markTestSkipped('Cannot write test caches due to permissions');
        }

        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function testRetrievingSmdCacheShouldReturnFalseIfCacheDoesNotExist(): void
    {
        $this->assertFalse(Server\Cache::getSmd($this->cacheFile));
    }

    public function testSavingSmdCacheShouldReturnTrueOnSuccess(): void
    {
        $this->assertTrue(Server\Cache::saveSmd($this->cacheFile, $this->server));
    }

    public function testSavedCacheShouldMatchGeneratedCache(): void
    {
        $this->testSavingSmdCacheShouldReturnTrueOnSuccess();
        $json = $this->server->getServiceMap()->toJSON();
        $test = Server\Cache::getSmd($this->cacheFile);
        $this->assertSame($json, $test);
    }

    public function testDeletingSmdShouldReturnFalseOnFailure(): void
    {
        $this->assertFalse(Server\Cache::deleteSmd($this->cacheFile));
    }

    public function testDeletingSmdShouldReturnTrueOnSuccess(): void
    {
        $this->testSavingSmdCacheShouldReturnTrueOnSuccess();
        $this->assertTrue(Server\Cache::deleteSmd($this->cacheFile));
    }
}
