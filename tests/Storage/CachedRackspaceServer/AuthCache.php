<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage\CachedRackspaceServer;

use Psr\SimpleCache\CacheInterface;

class AuthCache
{
    private static $cache;

    public static function getCache(): CacheInterface
    {
        if (!empty(self::$cache)) {
            return self::$cache;
        }

        return self::$cache = new class implements CacheInterface
        {
            private $data;

            public function get($key, $default = null)
            {
                if ($this->has($key)) {
                    return $this->data[$key];
                }

                return $default;
            }

            public function set($key, $value, $ttl = null)
            {
                return $this->data[$key] = $value;
            }

            public function delete($key)
            {
                unset($this->data[$key]);
            }

            public function clear()
            {
                $this->data = [];
            }

            public function getMultiple($keys, $default = null)
            {
                $result = [];
                foreach ($keys as $key) {
                    $result[$key] = $this->get($key, $default);
                }

                return $result;
            }

            public function setMultiple($values, $ttl = null)
            {
                foreach ($values as $key => $value) {
                    $this->set($key, $value);
                }
            }

            public function deleteMultiple($keys)
            {
                foreach ($keys as $key) {
                    $this->delete($key);
                }
            }

            public function has($key)
            {
                return isset($this->data[$key]);
            }
        };
    }
}