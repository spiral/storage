<?php
/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author    Anton Titov (Wolfy-J)
 */
define('SPIRAL_INITIAL_TIME', microtime(true));

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);
mb_internal_encoding('UTF-8');

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

//File component fixtures
define('FIXTURE_DIRECTORY', __DIR__ . '/Files/fixtures/');

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = getenv($key);
        if (is_null($value)) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;

            case 'false':
            case '(false)':
                return false;

            case 'null':
            case '(null)':
                return null;

            case 'empty':
            case '(empty)':
                return '';
        }

        return $value;
    }
}

//Always keep it outside of repo.
if (file_exists(dirname(__DIR__) . '/.env')) {
    $loader = new \Dotenv\Loader(dirname(__DIR__) . '/.env');
    $loader->load();
}

//Set to true to enter the Matrix
define('ENABLE_PROFILING', env('PROFILING', false));