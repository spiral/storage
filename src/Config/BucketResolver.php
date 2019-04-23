<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Storage\Config;

use Spiral\Storage\Exception\ResolveException;

final class BucketResolver
{
    /**
     * Bucket names associated with prefixes.
     *
     * @var array
     */
    private $prefixes = [];

    /**
     * @param array $prefixes
     */
    public function __construct(array $prefixes)
    {
        $this->prefixes = $prefixes;
    }

    /**
     * @param string $name
     * @param string $prefix
     */
    public function setBucket(string $name, string $prefix)
    {
        $this->prefixes[$name] = $prefix;
    }

    /**
     * Locate bucket name using object address.
     *
     * @param string $address
     * @param string $name
     * @return string
     *
     * @throws ResolveException
     */
    public function resolveBucket(string $address, string &$name = null): string
    {
        $bucket = null;
        $length = 0;
        foreach ($this->prefixes as $id => $prefix) {
            if (strpos($address, $prefix) === 0 && strlen($prefix) > $length) {
                $bucket = $id;
                $length = strlen($prefix);
            }
        }

        if (empty($bucket)) {
            throw new ResolveException("Unable to resolve bucket of `{$address}`.");
        }

        $name = substr($address, $length);

        return $bucket;
    }
}