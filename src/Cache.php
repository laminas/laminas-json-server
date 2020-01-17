<?php

/**
 * @see       https://github.com/laminas/laminas-json-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-json-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-json-server/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Json\Server;

use Laminas\Server\Cache as ServerCache;

use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_readable;
use function is_writable;
use function restore_error_handler;
use function set_error_handler;
use function unlink;

use const E_WARNING;

/**
 * Cache server definition and SMD.
 */
class Cache extends ServerCache
{
    /**
     * Cache a service map description (SMD) to a file
     *
     * Returns true on success, false on failure
     *
     * @param  string $filename
     * @param  Server $server
     * @return bool
     */
    public static function saveSmd(string $filename, Server $server) : bool
    {
        if (! file_exists($filename)
            && ! is_writable(dirname($filename))
        ) {
            return false;
        }

        set_error_handler(function ($errno, $errstr) {
            // swallow errors; method returns false on failure
        }, E_WARNING);

        $test = file_put_contents($filename, $server->getServiceMap()->toJson());

        restore_error_handler();

        if (0 === $test) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve a cached SMD
     *
     * On success, returns the cached SMD (a JSON string); a failure, returns
     * boolean false.
     *
     * @param  string $filename
     * @return string|false
     */
    public static function getSmd(string $filename)
    {
        if (! file_exists($filename)
            || ! is_readable($filename)
        ) {
            return false;
        }

        set_error_handler(function ($errno, $errstr) {
            // swallow errors; method returns false on failure
        }, E_WARNING);

        $smd = file_get_contents($filename);

        restore_error_handler();

        if (false === $smd) {
            return false;
        }

        return $smd;
    }

    /**
     * Delete a file containing a cached SMD
     *
     * @param  string $filename
     * @return bool
     */
    public static function deleteSmd(string $filename) : bool
    {
        if (file_exists($filename)) {
            unlink($filename);
            return true;
        }

        return false;
    }
}
