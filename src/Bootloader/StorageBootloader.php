<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Bootloader;

use Psr\Container\ContainerInterface;
use Spiral\Boot\FinalizerInterface;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Core\Bootloader\Bootloader;
use Spiral\Storage\StorageBucket;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\StorageInterface;
use Spiral\Storage\StorageManager;

class StorageBootloader extends Bootloader
{
    const BOOT = true;

    const SINGLETONS = [
        StorageInterface::class => StorageManager::class,
        StorageManager::class   => StorageManager::class
    ];

    const BINDINGS = [
        BucketInterface::class => StorageBucket::class
    ];

    /**
     * @param ConfiguratorInterface $configurator
     * @param FinalizerInterface    $finalizer
     * @param ContainerInterface    $container
     */
    public function boot(
        ConfiguratorInterface $configurator,
        FinalizerInterface $finalizer,
        ContainerInterface $container
    ) {
        $configurator->setDefaults('storage', [
            'servers' => [],
            'buckets' => [],
        ]);

        $finalizer->addFinalizer(function (bool $terminate) use ($container) {
            if ($terminate) {
                $container->get(StorageInterface::class)->disconnect();
            }
        });
    }
}