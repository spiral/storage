<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

interface SpecificConfigurableServerInfo
{
    public function constructSpecific(array $info): void;
}
