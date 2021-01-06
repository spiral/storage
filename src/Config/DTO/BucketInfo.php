<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\DTO\Traits\OptionsTrait;

class BucketInfo
{
    use OptionsTrait;

    private const OPTIONS_KEY = 'options';

    private const DIRECTORY_KEY = 'directory';

    public string $name;

    public ServerInfoInterface $serverInfo;

    public function __construct(string $name, ServerInfoInterface $serverInfo, array $info = [])
    {
        $this->name = $name;

        $this->serverInfo = $serverInfo;

        if (array_key_exists(static::OPTIONS_KEY, $info)) {
            $this->options = $info[static::OPTIONS_KEY];
        }
    }

    public function getDirectory(): ?string
    {
        return $this->getOption(static::DIRECTORY_KEY);
    }
}
