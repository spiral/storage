<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Config;

use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;
use Spiral\Core\Traits\Config\AliasTrait;
use Spiral\Storage\Exception\ConfigException;

class StorageConfig extends InjectableConfig
{
    use AliasTrait;

    const CONFIG = 'storage';

    /** @var array */
    protected $config = [
        'servers' => [],
        'buckets' => []
    ];

    /**
     * @return BucketResolver
     */
    public function getResolver(): BucketResolver
    {
        $prefixes = [];
        foreach ($this->config['buckets'] as $name => $options) {
            if (empty($options['prefix'])) {
                continue;
            }

            $prefixes[$name] = $options['prefix'];
        }

        return new BucketResolver($prefixes);
    }

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
            throw new ConfigException("Invalid server `{$server}` definition, `class` missing");
        }

        return new Autowire($wire['class'], $wire);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasBucket(string $name): bool
    {
        return isset($this->config['buckets'][$name]);
    }

    /**
     * Get bucket options.
     *
     * @param string $name
     * @return array
     *
     * @throws ConfigException
     */
    public function getBucket(string $name): array
    {
        if (!$this->hasBucket($name)) {
            throw new ConfigException("Undefined bucket `{$name}`");
        }

        $bucket = $this->config['buckets'][$name];

        if (!array_key_exists('options', $bucket)) {
            throw new ConfigException("Bucket `{$name}` must specify `options`");
        }

        if (!array_key_exists('prefix', $bucket)) {
            throw new ConfigException("Bucket `{$name}` must specify `prefix`");
        }

        if (!array_key_exists('server', $bucket)) {
            throw new ConfigException("Bucket `{$name}` must specify `server` name.");
        }

        return $bucket;
    }
}