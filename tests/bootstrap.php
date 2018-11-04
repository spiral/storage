<?php
/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author    Anton Titov (Wolfy-J)
 */

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

\Spiral\Storage\Tests\BaseTest::$OPTS = [
    'profile' => false,
    'home'    => __DIR__ . '/fixtures/',
    'mongodb' => [
        'conn'     => 'mongodb://localhost:27017',
        'database' => 'phpunit'
    ],
    'ftp'     => [
        'host'     => 'localhost',
        'username' => 'phpunit',
        'password' => 'phpunit'
    ],
    'sftp'    => [
        'host'     => 'localhost',
        'username' => 'phpunit',
        'password' => 'phpunit'
    ],
];