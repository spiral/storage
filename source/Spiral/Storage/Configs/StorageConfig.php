<?php
/**
 * Spiral Framework.
 *
 * @license MIT
 * @author  Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Configs;

use Spiral\Core\InjectableConfig;
use Spiral\Core\Traits\Config\AliasTrait;

/**
 * Storage manager configuration.
 */
class StorageConfig extends InjectableConfig
{
    use AliasTrait;

    /**
     * Configuration section.
     */
    const CONFIG = 'storage';

    /**
     * @var array
     */
    protected $config = [
        'servers' => [],
        'buckets' => []
    ];

    /**
     * @param string $server
     *
     * @return bool
     */
    public function hasServer(string $server): bool
    {
        return isset($this->config['servers'][$server]);
    }

    /**
     * @param string $server
     *
     * @return string
     */
    public function serverClass(string $server): string
    {
        return $this->config['servers'][$server]['class'];
    }

    /**
     * @param string $server
     *
     * @return array
     */
    public function serverOptions(string $server): array
    {
        $options = $this->config['servers'][$server];
        unset($options['class']);

        return $options;
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