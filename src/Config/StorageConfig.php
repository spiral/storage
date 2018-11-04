<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Config;

use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;
use Spiral\Storage\Exception\ConfigException;

class StorageConfig extends InjectableConfig
{
    const CONFIG = 'storage';

    /** @var array */
    protected $config = [
        'servers' => [],
        'buckets' => []
    ];

    /**
     * @param string $server
     * @return bool
     */
    public function hasServer(string $server): bool
    {
        return isset($this->config['servers'][$server]);
    }

    /**
     * @param string $server
     * @return Autowire
     */
    public function getServer(string $server): Autowire
    {
        if (!$this->hasServer($server)) {
            throw new ConfigException("Undefined server `{$server}`");
        }

        $wire = $this->config['servers'][$server];
        if ($wire instanceof Autowire) {
            return $wire;
        }

        if (is_string($wire)) {
            return new Autowire($wire);
        }

        if (!isset($wire['class'])) {
            throw new ConfigException("Invalid server `{$server}` definition, `class` missing.");
        }

        return new Autowire($wire['class'], $wire);
    }

    /**
     * Every available bucket with it's config.
     *
     * @return array
     */
    public function getBuckets(): array
    {
        return $this->config['buckets'];
    }
}