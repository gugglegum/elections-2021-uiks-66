<?php

namespace App\Cache;

use Phpfastcache\Drivers\Files\Config;
use Phpfastcache\Drivers\Files\Driver;

class Cache extends Driver
{
    public function __construct(array $config = [])
    {
        $config = (new Config())
            ->setPath(PROJECT_ROOT_DIR . '/data/cache');
        parent::__construct($config, '');
    }
}
