<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage;

use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\ServerInterface;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    const PROFILING = ENABLE_PROFILING;

    protected $skipped = false;

    protected function getStreamSource(): StreamInterface
    {
        $content = random_bytes(mt_rand(100, 100000));

        return \GuzzleHttp\Psr7\stream_for($content);
    }

    abstract protected function getBucket(): BucketInterface;

    abstract protected function secondaryBucket(): BucketInterface;

    abstract protected function getServer(): ServerInterface;

    protected function makeLogger()
    {
        if (static::PROFILING) {
            return new class implements LoggerInterface
            {
                use LoggerTrait;

                public function log($level, $message, array $context = [])
                {
                    if ($level == LogLevel::ERROR) {
                        echo " \n! \033[31m" . $message . "\033[0m";
                    } elseif ($level == LogLevel::ALERT) {
                        echo " \n! \033[35m" . $message . "\033[0m";
                    } else {
                        echo " \n> \033[33m" . $message . "\033[0m";
                    }
                }
            };
        }

        return new NullLogger();
    }
}