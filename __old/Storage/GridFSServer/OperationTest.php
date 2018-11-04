<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage\GridFSServer;

/**
 * @see https://github.com/mongodb/mongo-php-library/issues/317
 * @see https://github.com/slimphp/Slim/issues/2112
 * @see https://jira.mongodb.org/browse/PHPLIB-213
 */
class OperationTest extends \Spiral\Tests\Storage\OperationTest
{
    use ServerTrait;
}